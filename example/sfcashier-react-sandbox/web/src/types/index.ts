export interface ApiCollection<T> {
  "@context": string;
  "@id": string;
  "@type": string;
  member: T[];
  "hydra:member"?: T[];
  totalItems: number;
  "hydra:totalItems"?: number;
  "hydra:view"?: {
    "@id": string;
    "@type": string;
    "hydra:first": string;
    "hydra:last": string;
    "hydra:next"?: string;
  };
}

export interface Product {
  "@id": string;
  "@type": string;
  id: number;
  slug: string;
  name: string;
  description: string;
  price: number; // centimes
  imageUrl?: string;
  stock: number;
}

export interface CartItem {
  "@id": string;
  id: number;
  product: Product;
  quantity: number;
}

export interface Cart {
  "@id": string;
  id: number;
  items: CartItem[];
}

export interface Order {
  "@id": string;
  id: number;
  orderNumber: string;
  userId?: number;
  total: number;
  status: string;
  createdAt: string;
  itemCount?: number;
  detailPath?: string;
  stripeCheckoutSessionId?: string | null;
  items?: OrderItem[];
  invoice?: OrderInvoice;
}

export interface OrderInvoice {
  id: number;
  stripeInvoiceId?: string | null;
  filename: string;
  relativePath: string;
  mimeType: string;
  size: number;
  status: string;
  amountTotal: number;
  currency: string;
  createdAt: string;
  downloadPath: string;
  hostedInvoiceUrl?: string | null;
}

export interface OrderItem {
  id: number;
  productId: number;
  productName: string;
  productSlug: string;
  productDescription: string | null;
  productImageUrl: string | null;
  unitPrice: number;
  quantity: number;
  subtotal: number;
}

export interface CheckoutSessionResponse {
  "@id": string;
  "@type": string;
  order: Order;
  checkoutUrl: string;
  sessionId: string;
}

export interface DeleteOrdersResponse {
  deletedOrders: number;
  deletedInvoices: number;
  deletedInvoiceFiles: number;
}

export interface SubscriptionPlanPrice {
  amount: number;
  currency: string;
  priceId?: string;
  monthlyEquivalent?: number;
  label: string;
}

export interface SubscriptionPlan {
  code: string;
  name: string;
  description: string;
  trialDays: number;
  yearlyDiscountPercent: number;
  features: string[];
  monthly: SubscriptionPlanPrice;
  yearly: SubscriptionPlanPrice;
}

export interface SubscriptionCheckoutSessionResponse {
  "@id": string;
  "@type": string;
  planCode: string;
  billingCycle: "monthly" | "yearly";
  checkoutUrl: string;
  sessionId: string;
}

export interface CurrentSubscriptionPlanSummary {
  code: string;
  name: string;
  billingCycle: "monthly" | "yearly";
  trialDays: number;
}

export interface CurrentSubscription {
  id: number;
  type: string;
  stripeId: string;
  stripeStatus: string;
  stripePrice?: string | null;
  quantity?: number | null;
  trialEndsAt?: string | null;
  endsAt?: string | null;
  createdAt: string;
  updatedAt: string;
  isActive: boolean;
  isOnTrial: boolean;
  isOnGracePeriod: boolean;
  canCancel: boolean;
  canResume: boolean;
  canManageBilling: boolean;
  plan?: CurrentSubscriptionPlanSummary | null;
}

export interface SubscriptionStateResponse {
  "@id": string;
  "@type": string;
  hasSubscription: boolean;
  subscription: CurrentSubscription | null;
}

export interface BillingPortalResponse {
  url: string;
}

export interface User {
  "@id": string;
  id: number;
  email: string;
  name: string;
}

export interface QuickLoginRequest {
  email: string;
}

export interface QuickLoginResponse {
  user: User;
}

export interface AddToCartRequest {
  product: string; // Product IRI
  quantity: number;
}

export interface GuestCartItem {
  productId: number;
  productIri: string; // "/api/v1/products/26"
  quantity: number;
  name: string;
  slug: string;
  price: number;
  imageUrl?: string;
  stock: number;
  description: string;
}

export interface GuestCartPayloadItem {
  productId: number;
  quantity: number;
}

export interface GuestCartResponse {
  items: GuestCartItem[];
  total: number;
}

export interface CartSyncRequest {
  items: { productId: number; quantity: number }[];
}

// Auth Types (BetterAuth)
export interface AuthTokens {
  access_token: string;
  refresh_token: string;
  expires_in: number;
}

export interface AuthResponse {
  user: User;
  access_token: string;
  refresh_token: string;
  expires_in: number;
}

export interface TwoFactorSetupResponse {
  qrCode: string;
  secret: string;
  backupCodes: string[];
}

export interface TwoFactorVerifyRequest {
  code: string;
  secret: string;
}

export interface LoginResponse {
  user?: User;
  access_token?: string;
  refresh_token?: string;
  expires_in?: number;
  requires2fa?: boolean;
  requires_2fa?: boolean; // backward compatibility
}

export interface DemoUser {
  name: string;
  email: string;
  password: string | null;
}
