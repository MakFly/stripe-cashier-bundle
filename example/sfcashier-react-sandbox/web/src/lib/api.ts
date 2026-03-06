import type {
  ApiCollection,
  Product,
  Cart,
  CartItem,
  Order,
  User,
  AddToCartRequest,
  CartSyncRequest,
  GuestCartPayloadItem,
  GuestCartResponse,
  AuthTokens,
  AuthResponse,
  TwoFactorSetupResponse,
  LoginResponse,
  DemoUser,
  CheckoutSessionResponse,
  DeleteOrdersResponse,
  SubscriptionPlan,
  SubscriptionCheckoutSessionResponse,
  SubscriptionStateResponse,
  BillingPortalResponse,
} from "../types";

const API_URL = import.meta.env.VITE_API_URL || "/api/v1";
let refreshInFlight: Promise<void> | null = null;

export class ApiError extends Error {
  public readonly status: number;

  constructor(
    status: number,
    message: string,
  ) {
    super(message);
    this.status = status;
    this.name = "ApiError";
  }
}

export async function apiClient<T>(
  endpoint: string,
  options: RequestInit = {},
): Promise<T> {
  const headers: Record<string, string> = {
    "Content-Type": "application/ld+json",
    Accept: "application/ld+json",
    ...(options.headers as Record<string, string> | undefined),
  };

  const doFetch = async (): Promise<Response> =>
    fetch(`${API_URL}${endpoint}`, {
      ...options,
      headers,
      credentials: "include",
    });

  let response = await doFetch();

  const isAuthEndpoint = endpoint.startsWith("/auth/");
  if (response.status === 401 && !isAuthEndpoint) {
    if (refreshInFlight === null) {
      refreshInFlight = refreshTokens().then(() => undefined).finally(() => {
        refreshInFlight = null;
      });
    }

    try {
      await refreshInFlight;
      response = await doFetch();
    } catch {
      // Refresh failed: keep original 401 handling below
    }
  }

  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    const message =
      body["hydra:description"] ?? body.error ?? response.statusText;
    throw new ApiError(response.status, message);
  }

  return response.json();
}

function normalizeCollection<T>(raw: ApiCollection<T>): ApiCollection<T> {
  const members = raw.member ?? raw["hydra:member"] ?? [];
  const total = raw.totalItems ?? raw["hydra:totalItems"] ?? members.length;

  return {
    ...raw,
    member: members,
    totalItems: total,
  };
}

// Auth API

export async function register(
  email: string,
  password: string,
  name: string,
): Promise<AuthResponse> {
  const response = await fetch(`${API_URL}/auth/register`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({ email, password, name }),
  });

  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    throw new ApiError(response.status, body.error ?? response.statusText);
  }

  const data = normalizeAuthResponse(await response.json());
  return data;
}

export async function login(
  email: string,
  password: string,
): Promise<AuthResponse | { requires_2fa: true }> {
  const response = await fetch(`${API_URL}/auth/login`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({ email, password }),
  });

  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    throw new ApiError(response.status, body.error ?? response.statusText);
  }

  const data = (await response.json()) as LoginResponse & {
    username?: string;
    email?: string;
  };

  if (data.requires2fa || data.requires_2fa) {
    return { requires_2fa: true };
  }

  return normalizeAuthResponse(data);
}

export async function verify2FA(
  code: string,
  email: string,
  password: string,
): Promise<AuthResponse> {
  const response = await fetch(`${API_URL}/auth/login/2fa`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({ email, password, code }),
  });

  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    throw new ApiError(response.status, body.error ?? response.statusText);
  }

  const data = normalizeAuthResponse(await response.json());
  return data;
}

export async function sendMagicLink(email: string): Promise<void> {
  const response = await fetch(`${API_URL}/auth/magic-link/send`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({ email }),
  });

  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    throw new ApiError(response.status, body.error ?? response.statusText);
  }
}

export async function verifyMagicLink(token: string): Promise<AuthResponse> {
  const response = await fetch(`${API_URL}/auth/magic-link/verify`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({ token }),
  });

  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    throw new ApiError(response.status, body.error ?? response.statusText);
  }

  const data = normalizeAuthResponse(await response.json());
  return data;
}

export async function setup2FA(): Promise<TwoFactorSetupResponse> {
  return apiClient<TwoFactorSetupResponse>("/auth/2fa/setup", {
    method: "POST",
  });
}

export async function getDemoUsers(
  withPassword: boolean,
): Promise<DemoUser[]> {
  const response = await fetch(
    `${API_URL}/auth/demo-users?withPassword=${withPassword ? "1" : "0"}`,
    {
      headers: { Accept: "application/json" },
      credentials: "include",
    },
  );

  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    throw new ApiError(response.status, body.error ?? response.statusText);
  }

  const data = (await response.json()) as { users: DemoUser[] };
  return data.users;
}

export async function verify2FASetup(
  code: string,
  _secret: string,
): Promise<void> {
  await apiClient("/auth/2fa/validate", {
    method: "POST",
    body: JSON.stringify({ code }),
  });
}

export async function refreshTokens(): Promise<AuthTokens> {
  const response = await fetch(`${API_URL}/auth/refresh`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({}),
  });

  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    throw new ApiError(response.status, body.error ?? response.statusText);
  }

  return response.json() as Promise<AuthTokens>;
}

export async function logout(): Promise<void> {
  await fetch(`${API_URL}/auth/logout`, {
    method: "POST",
    credentials: "include",
  }).catch(() => undefined);
}

export async function getCurrentUser(): Promise<User | null> {
  try {
    const user = await apiClient<
      User & { username?: string; name?: string }
    >("/auth/me");

    return {
      ...user,
      name: user.name ?? user.username ?? user.email,
    };
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      return null;
    }
    throw error;
  }
}

type BetterAuthAuthResponse = AuthResponse & {
  user: User & { username?: string; name?: string };
};

function normalizeAuthResponse(raw: unknown): AuthResponse {
  const data = raw as Partial<BetterAuthAuthResponse>;
  const user = (data.user ?? {}) as User & { username?: string; name?: string };

  return {
    access_token: data.access_token ?? "",
    refresh_token: data.refresh_token ?? "",
    expires_in: data.expires_in ?? 3600,
    ...data,
    user: {
      ...user,
      id: user.id ?? 0,
      email: user.email ?? "",
      name: user.name ?? user.username ?? user.email ?? "",
      "@id": user["@id"] ?? "",
    },
  };
}

// Products API
export async function getProducts(): Promise<ApiCollection<Product>> {
  const data = await apiClient<ApiCollection<Product>>("/products");
  return normalizeCollection(data);
}

export async function getProduct(slug: string): Promise<Product> {
  return apiClient<Product>(`/products/${slug}`);
}

// Cart API
export async function getCart(): Promise<Cart> {
  return apiClient<Cart>("/cart");
}

export async function addToCart(
  productIri: string,
  quantity: number,
): Promise<CartItem> {
  return apiClient<CartItem>("/cart/items", {
    method: "POST",
    body: JSON.stringify({
      product: productIri,
      quantity,
    } satisfies AddToCartRequest),
  });
}

export async function updateCartItem(
  itemId: number,
  quantity: number,
): Promise<CartItem> {
  return apiClient<CartItem>(`/cart/items/${itemId}`, {
    method: "PATCH",
    body: JSON.stringify({ quantity }),
  });
}

export async function removeFromCart(itemId: number): Promise<void> {
  await apiClient(`/cart/items/${itemId}`, { method: "DELETE" });
}

export async function clearCart(): Promise<void> {
  await apiClient("/cart/clear", { method: "POST" });
}

// Orders API
export async function getOrders(): Promise<ApiCollection<Order>> {
  const data = await apiClient<ApiCollection<Order>>("/orders");
  return normalizeCollection(data);
}

export async function createOrder(): Promise<Order> {
  return apiClient<Order>("/orders", { method: "POST" });
}

export async function getOrder(id: number): Promise<Order> {
  return apiClient<Order>(`/orders/${id}`);
}

export async function getOrderForUser(
  userId: number,
  orderId: number,
): Promise<Order> {
  return apiClient<Order>(`/orders/user/${userId}/${orderId}`);
}

export async function deleteAllOrders(): Promise<DeleteOrdersResponse> {
  return apiClient<DeleteOrdersResponse>("/orders", {
    method: "DELETE",
  });
}

export async function createCheckoutSession(): Promise<CheckoutSessionResponse> {
  return apiClient<CheckoutSessionResponse>("/orders/checkout/session", {
    method: "POST",
  });
}

export async function confirmCheckoutSession(
  orderId: number,
  sessionId: string,
): Promise<Order> {
  return apiClient<Order>("/orders/checkout/confirm", {
    method: "POST",
    body: JSON.stringify({ orderId, sessionId }),
  });
}

export async function syncCart(data: CartSyncRequest): Promise<Cart> {
  return apiClient<Cart>("/cart/sync", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function getGuestCart(): Promise<GuestCartResponse> {
  const response = await fetch(`${API_URL}/cart/guest`, {
    method: "GET",
    headers: { Accept: "application/json" },
    credentials: "include",
  });

  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    const message =
      body["hydra:description"] ?? body.error ?? response.statusText;
    throw new ApiError(response.status, message);
  }

  return response.json() as Promise<GuestCartResponse>;
}

export async function saveGuestCart(
  items: GuestCartPayloadItem[],
): Promise<GuestCartResponse> {
  const response = await fetch(`${API_URL}/cart/guest`, {
    method: "PUT",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    credentials: "include",
    body: JSON.stringify({ items }),
  });

  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    throw new ApiError(response.status, body.error ?? response.statusText);
  }

  return response.json() as Promise<GuestCartResponse>;
}

export async function clearGuestCartCookie(): Promise<void> {
  const response = await fetch(`${API_URL}/cart/guest`, {
    method: "DELETE",
    headers: { Accept: "application/json" },
    credentials: "include",
  });

  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    throw new ApiError(response.status, body.error ?? response.statusText);
  }
}

export async function mergeGuestCart(): Promise<Cart> {
  return apiClient<Cart>("/cart/merge-guest", {
    method: "POST",
  });
}

// Subscriptions API
export async function getSubscriptionPlans(): Promise<ApiCollection<SubscriptionPlan>> {
  const data = await apiClient<ApiCollection<SubscriptionPlan>>("/subscriptions/plans", {
    headers: {
      Accept: "application/json",
    },
  });

  return normalizeCollection(data);
}

export async function getCurrentSubscription(): Promise<SubscriptionStateResponse> {
  return apiClient<SubscriptionStateResponse>("/subscriptions/current", {
    headers: {
      Accept: "application/json",
    },
  });
}

export async function createSubscriptionCheckoutSession(
  planCode: string,
  billingCycle: "monthly" | "yearly",
): Promise<SubscriptionCheckoutSessionResponse> {
  return apiClient<SubscriptionCheckoutSessionResponse>("/subscriptions/checkout/session", {
    method: "POST",
    body: JSON.stringify({ planCode, billingCycle }),
  });
}

export async function getBillingPortalUrl(): Promise<BillingPortalResponse> {
  return apiClient<BillingPortalResponse>("/subscriptions/portal", {
    method: "GET",
    headers: {
      Accept: "application/json",
    },
  });
}

export async function cancelSubscription(): Promise<SubscriptionStateResponse> {
  return apiClient<SubscriptionStateResponse>("/subscriptions/cancel", {
    method: "POST",
  });
}

export async function resumeSubscription(): Promise<SubscriptionStateResponse> {
  return apiClient<SubscriptionStateResponse>("/subscriptions/resume", {
    method: "POST",
  });
}
