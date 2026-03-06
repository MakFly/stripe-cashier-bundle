import { create } from "zustand";
import { toast } from "sonner";
import type { AuthResponse, User } from "../types";
import {
  register as apiRegister,
  login as apiLogin,
  verify2FA as apiVerify2FA,
  sendMagicLink as apiSendMagicLink,
  verifyMagicLink as apiVerifyMagicLink,
  logout as apiLogout,
  getCurrentUser,
} from "../lib/api";
import { useCartStore } from "./cart";

interface AuthState {
  user: User | null;
  isLoading: boolean;
  initialized: boolean;
  requires2FA: boolean;
  twoFactorEmail: string | null;
  twoFactorPassword: string | null;
  setUser: (user: User | null) => void;
  register: (email: string, password: string, name: string) => Promise<void>;
  login: (email: string, password: string) => Promise<void>;
  verify2FA: (code: string) => Promise<void>;
  sendMagicLink: (email: string) => Promise<void>;
  verifyMagicLink: (token: string, silent?: boolean) => Promise<void>;
  logout: () => Promise<void>;
  fetchUser: () => Promise<void>;
}

export const useAuthStore = create<AuthState>((set, get) => {
  const finalizeAuthSuccess = async (
    user: User,
    options?: { silent?: boolean },
  ): Promise<void> => {
    const silent = options?.silent ?? false;

    set({
      user,
      isLoading: false,
      initialized: true,
      requires2FA: false,
      twoFactorEmail: null,
      twoFactorPassword: null,
    });

    try {
      await useCartStore.getState().mergeGuestCartToUser();
    } catch {
      await useCartStore.getState().fetchCart().catch(() => {});
    }

    if (!silent) {
      toast.success(`Bienvenue, ${user.name} !`);
    }
  };

  return {
    user: null,
    isLoading: false,
    initialized: false,
    requires2FA: false,
    twoFactorEmail: null,
    twoFactorPassword: null,
    setUser: (user) => set({ user }),

    register: async (email: string, password: string, name: string) => {
      set({ isLoading: true });
      try {
        const data = await apiRegister(email, password, name);
        await finalizeAuthSuccess(data.user);
      } catch (error) {
        set({ isLoading: false });
        toast.error("Inscription échouée");
        throw error;
      }
    },

    login: async (email: string, password: string) => {
      set({
        isLoading: true,
        requires2FA: false,
        twoFactorEmail: null,
        twoFactorPassword: null,
      });
      try {
        const result = await apiLogin(email, password);

        if ("requires_2fa" in result && result.requires_2fa) {
          set({
            requires2FA: true,
            twoFactorEmail: email,
            twoFactorPassword: password,
            isLoading: false,
          });
          return;
        }

        const authResult = result as AuthResponse;
        await finalizeAuthSuccess(authResult.user);
      } catch (error) {
        set({ isLoading: false });
        toast.error("Email ou mot de passe incorrect");
        throw error;
      }
    },

    verify2FA: async (code: string) => {
      const { twoFactorEmail, twoFactorPassword } = get();
      if (!twoFactorEmail || !twoFactorPassword) {
        throw new Error("No pending 2FA login");
      }

      set({ isLoading: true });
      try {
        const data = await apiVerify2FA(code, twoFactorEmail, twoFactorPassword);
        await finalizeAuthSuccess(data.user);
      } catch (error) {
        set({ isLoading: false });
        toast.error("Code 2FA invalide");
        throw error;
      }
    },

    sendMagicLink: async (email: string) => {
      set({ isLoading: true });
      try {
        await apiSendMagicLink(email);
        set({ isLoading: false });
        toast.success("Lien de connexion envoyé par email");
      } catch (error) {
        set({ isLoading: false });
        toast.error("Erreur lors de l'envoi du lien");
        throw error;
      }
    },

    verifyMagicLink: async (token: string, silent: boolean = false) => {
      set({ isLoading: true });
      try {
        const data = await apiVerifyMagicLink(token);
        await finalizeAuthSuccess(data.user, { silent });
      } catch (error) {
        set({ isLoading: false });
        if (!silent) {
          toast.error("Lien invalide ou expiré");
        }
        throw error;
      }
    },

    logout: async () => {
      set({ isLoading: true });
      try {
        await apiLogout();
      } catch (error) {
        // Ignore logout errors, always clear local state
      } finally {
        set({
          user: null,
          isLoading: false,
          initialized: true,
          requires2FA: false,
          twoFactorEmail: null,
          twoFactorPassword: null,
        });
        useCartStore.getState().clearItems();
        toast.success("À bientôt !");
      }
    },

    fetchUser: async () => {
      const { initialized, isLoading } = get();
      if (initialized || isLoading) return;

      set({ isLoading: true });
      const user = await getCurrentUser();
      set({ user, isLoading: false, initialized: true });
    },
  };
});
