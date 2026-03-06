import { useState, type FormEvent, useEffect } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { useAuthStore } from "../stores/auth";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "../components/ui/card";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { Button } from "../components/ui/button";
import { OtpInput } from "../components/ui/otp-input";
import { Skeleton } from "../components/ui/skeleton";
import type { DemoUser } from "../types";
import { getDemoUsers } from "../lib/api";
import { Package, Mail, Lock, User, ArrowRight, Loader2, Shield } from "lucide-react";
import { cn } from "../lib/utils";

type TabType = "login" | "register" | "magic-link";
const demoUsersCache = new Map<boolean, DemoUser[]>();
const demoUsersInFlight = new Map<boolean, Promise<DemoUser[]>>();

async function fetchDemoUsers(withPassword: boolean): Promise<DemoUser[]> {
  const cached = demoUsersCache.get(withPassword);
  if (cached) return cached;

  const pending = demoUsersInFlight.get(withPassword);
  if (pending) return pending;

  const request = getDemoUsers(withPassword)
    .then((users) => {
      demoUsersCache.set(withPassword, users);
      return users;
    })
    .finally(() => {
      demoUsersInFlight.delete(withPassword);
    });

  demoUsersInFlight.set(withPassword, request);
  return request;
}

export const LoginPage = () => {
  const [searchParams] = useSearchParams();
  const tabParam = searchParams.get("tab");
  const initialTab: TabType =
    tabParam === "login" || tabParam === "register" || tabParam === "magic-link"
      ? tabParam
      : "login";
  const callbackError = searchParams.get("error");
  const callbackErrorMessage =
    callbackError === "magic_link_invalid"
      ? "Lien invalide ou expiré"
      : callbackError === "magic_link_missing"
        ? "Lien de connexion invalide"
        : "";
  const navigate = useNavigate();
  const redirect = searchParams.get("redirect") || "/";
  const {
    login,
    register,
    verify2FA,
    sendMagicLink,
    user,
    isLoading,
    requires2FA,
    initialized,
  } = useAuthStore();

  const [activeTab, setActiveTab] = useState<TabType>(initialTab);
  const [error, setError] = useState("");
  const [otpCode, setOtpCode] = useState("");

  // Login form state
  const [loginEmail, setLoginEmail] = useState("");
  const [loginPassword, setLoginPassword] = useState("");

  // Register form state
  const [registerName, setRegisterName] = useState("");
  const [registerEmail, setRegisterEmail] = useState("");
  const [registerPassword, setRegisterPassword] = useState("");

  // Magic link form state
  const [magicLinkEmail, setMagicLinkEmail] = useState("");
  const [magicLinkSent, setMagicLinkSent] = useState(false);
  const [demoUsers, setDemoUsers] = useState<DemoUser[]>([]);
  const [selectedDemoUser, setSelectedDemoUser] = useState("");

  useEffect(() => {
    if (initialized && user) {
      navigate(redirect, { replace: true });
    }
  }, [initialized, user, redirect, navigate]);

  useEffect(() => {
    const withPassword = activeTab === "login";
    fetchDemoUsers(withPassword)
      .then((users) => setDemoUsers(users))
      .catch(() => setDemoUsers([]));
  }, [activeTab]);

  const applyDemoUser = (email: string) => {
    setSelectedDemoUser(email);
    const demoUser = demoUsers.find((u) => u.email === email);
    if (!demoUser) return;

    if (activeTab === "login") {
      setLoginEmail(demoUser.email);
      setLoginPassword(demoUser.password ?? "");
      return;
    }

    if (activeTab === "register") {
      setRegisterName(demoUser.name);
      setRegisterEmail(demoUser.email);
      return;
    }

    setMagicLinkEmail(demoUser.email);
  };

  const handleLoginSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError("");

    if (!loginEmail || !loginPassword) {
      setError("Veuillez remplir tous les champs");
      return;
    }

    try {
      await login(loginEmail, loginPassword);
      if (!useAuthStore.getState().requires2FA) {
        navigate(redirect, { replace: true, viewTransition: true });
      }
    } catch {
      setError("Email ou mot de passe incorrect");
    }
  };

  const handleRegisterSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError("");

    if (!registerEmail || !registerPassword || !registerName) {
      setError("Veuillez remplir tous les champs");
      return;
    }

    if (registerPassword.length < 8) {
      setError("Le mot de passe doit contenir au moins 8 caractères");
      return;
    }

    try {
      await register(registerEmail, registerPassword, registerName);
      navigate(redirect, { replace: true, viewTransition: true });
    } catch {
      setError("Inscription échouée. Cet email est peut-être déjà utilisé.");
    }
  };

  const handleMagicLinkSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError("");

    if (!magicLinkEmail) {
      setError("Veuillez entrer votre email");
      return;
    }

    try {
      await sendMagicLink(magicLinkEmail);
      setMagicLinkSent(true);
    } catch {
      setError("Erreur lors de l'envoi du lien");
    }
  };

  const handle2FAVerification = async () => {
    setError("");

    if (otpCode.length !== 6) {
      setError("Veuillez entrer le code à 6 chiffres");
      return;
    }

    try {
      await verify2FA(otpCode);
      navigate(redirect, { replace: true, viewTransition: true });
    } catch {
      setError("Code incorrect");
    }
  };

  const displayedError = error || callbackErrorMessage;

  if (!initialized) {
    return (
      <div className="min-h-[calc(100vh-8rem)] flex items-center justify-center px-4 py-12">
        <div className="w-full max-w-md space-y-4">
          <Skeleton className="h-12 w-32 mx-auto rounded-xl" />
          <Skeleton className="h-80 w-full rounded-2xl" />
        </div>
      </div>
    );
  }

  if (user) {
    return null;
  }

  // Show 2FA verification screen if required
  if (requires2FA) {
    return (
      <div className="min-h-[calc(100vh-8rem)] flex items-center justify-center px-4 py-12">
        <div className="absolute inset-0 overflow-hidden pointer-events-none">
          <div className="absolute top-1/4 left-1/4 w-96 h-96 rounded-full bg-primary/5 blur-3xl" />
          <div className="absolute bottom-1/4 right-1/4 w-96 h-96 rounded-full bg-accent/5 blur-3xl" />
        </div>

        <div className="relative w-full max-w-md animate-fade-up">
          <div className="text-center mb-8">
            <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-primary mb-4 shadow-glow">
              <Shield className="h-8 w-8 text-primary-foreground" />
            </div>
            <h1
              className="text-3xl font-medium"
              style={{ fontFamily: "'Newsreader', serif" }}
            >
              SFCashier
            </h1>
            <p className="text-muted-foreground text-sm mt-1">
              Vérification en deux étapes
            </p>
          </div>

          <Card className="border-0 shadow-elevated overflow-hidden">
            <div className="bg-gradient-to-br from-primary/5 via-transparent to-accent/5">
              <CardHeader className="text-center pb-2">
                <CardTitle
                  className="text-2xl"
                  style={{ fontFamily: "'Newsreader', serif" }}
                >
                  Code de vérification
                </CardTitle>
                <CardDescription>
                  Entrez le code à 6 chiffres généré par votre application
                  d'authentification
                </CardDescription>
              </CardHeader>
              <CardContent className="pt-4">
                <div className="space-y-6">
                  <div className="flex justify-center">
                    <OtpInput
                      value={otpCode}
                      onChange={setOtpCode}
                      disabled={isLoading}
                      autoFocus
                    />
                  </div>

                  {displayedError && (
                    <div className="rounded-xl bg-destructive/10 border border-destructive/20 p-4 animate-scale-in">
                      <p className="text-sm text-destructive font-medium">
                        {displayedError}
                      </p>
                    </div>
                  )}

                  <Button
                    onClick={handle2FAVerification}
                    size="lg"
                    className="w-full h-12 rounded-xl btn-press shadow-glow"
                    disabled={isLoading || otpCode.length !== 6}
                  >
                    {isLoading ? (
                      <>
                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        Vérification...
                      </>
                    ) : (
                      <>
                        Vérifier
                        <ArrowRight className="ml-2 h-4 w-4" />
                      </>
                    )}
                  </Button>
                </div>
              </CardContent>
            </div>
          </Card>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-[calc(100vh-8rem)] flex items-center justify-center px-4 py-12">
      <div className="absolute inset-0 overflow-hidden pointer-events-none">
        <div className="absolute top-1/4 left-1/4 w-96 h-96 rounded-full bg-primary/5 blur-3xl" />
        <div className="absolute bottom-1/4 right-1/4 w-96 h-96 rounded-full bg-accent/5 blur-3xl" />
      </div>

      <div className="relative w-full max-w-md animate-fade-up">
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-primary mb-4 shadow-glow">
            <Package className="h-8 w-8 text-primary-foreground" />
          </div>
          <h1
            className="text-3xl font-medium"
            style={{ fontFamily: "'Newsreader', serif" }}
          >
            SFCashier
          </h1>
          <p className="text-muted-foreground text-sm mt-1">
            Connectez-vous à votre espace
          </p>
        </div>

        <Card className="border-0 shadow-elevated overflow-hidden">
          <div className="bg-gradient-to-br from-primary/5 via-transparent to-accent/5">
            {/* Tabs */}
            <div className="flex border-b border-border/50">
              <button
                onClick={() => setActiveTab("login")}
                className={cn(
                  "flex-1 py-3 text-sm font-medium transition-colors relative",
                  activeTab === "login"
                    ? "text-foreground"
                    : "text-muted-foreground hover:text-foreground",
                )}
              >
                Connexion
                {activeTab === "login" && (
                  <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-primary" />
                )}
              </button>
              <button
                onClick={() => setActiveTab("register")}
                className={cn(
                  "flex-1 py-3 text-sm font-medium transition-colors relative",
                  activeTab === "register"
                    ? "text-foreground"
                    : "text-muted-foreground hover:text-foreground",
                )}
              >
                Inscription
                {activeTab === "register" && (
                  <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-primary" />
                )}
              </button>
              <button
                onClick={() => setActiveTab("magic-link")}
                className={cn(
                  "flex-1 py-3 text-sm font-medium transition-colors relative",
                  activeTab === "magic-link"
                    ? "text-foreground"
                    : "text-muted-foreground hover:text-foreground",
                )}
              >
                Lien magique
                {activeTab === "magic-link" && (
                  <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-primary" />
                )}
              </button>
            </div>

            <CardHeader className="text-center pb-2">
              <CardTitle
                className="text-2xl"
                style={{ fontFamily: "'Newsreader', serif" }}
              >
                {activeTab === "login" && "Bienvenue"}
                {activeTab === "register" && "Créer un compte"}
                {activeTab === "magic-link" && "Connexion sans mot de passe"}
              </CardTitle>
              <CardDescription>
                {activeTab === "login" &&
                  "Entrez vos identifiants pour accéder à votre compte"}
                {activeTab === "register" &&
                  "Rejoignez-nous en quelques secondes"}
                {activeTab === "magic-link" &&
                  "Recevez un lien de connexion par email"}
              </CardDescription>
            </CardHeader>

            <CardContent className="pt-4">
              {demoUsers.length > 0 && (
                <div className="mb-4 space-y-2">
                  <Label htmlFor="demo-user" className="text-sm font-medium">
                    Compte de démo
                  </Label>
                  <select
                    id="demo-user"
                    value={selectedDemoUser}
                    onChange={(e) => applyDemoUser(e.target.value)}
                    className="w-full h-12 rounded-xl border border-input bg-background px-3 text-sm"
                  >
                    <option value="">Choisir un utilisateur...</option>
                    {demoUsers.map((demoUser) => (
                      <option key={demoUser.email} value={demoUser.email}>
                        {demoUser.name} ({demoUser.email})
                      </option>
                    ))}
                  </select>
                  <p className="text-xs text-muted-foreground">
                    {activeTab === "login"
                      ? "Email et mot de passe sont pré-remplis."
                      : "Seul l'email est pré-rempli pour cet onglet."}
                  </p>
                </div>
              )}

              {displayedError && (
                <div className="mb-4 rounded-xl bg-destructive/10 border border-destructive/20 p-4 animate-scale-in">
                  <p className="text-sm text-destructive font-medium">
                    {displayedError}
                  </p>
                </div>
              )}

              {/* Login Form */}
              {activeTab === "login" && (
                <form onSubmit={handleLoginSubmit} className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="login-email" className="text-sm font-medium">
                      Adresse email
                    </Label>
                    <div className="relative">
                      <Mail className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                      <Input
                        id="login-email"
                        type="email"
                        placeholder="vous@exemple.com"
                        value={loginEmail}
                        onChange={(e) => setLoginEmail(e.target.value)}
                        disabled={isLoading}
                        className="pl-10 h-12 rounded-xl"
                      />
                    </div>
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="login-password" className="text-sm font-medium">
                      Mot de passe
                    </Label>
                    <div className="relative">
                      <Lock className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                      <Input
                        id="login-password"
                        type="password"
                        placeholder="••••••••"
                        value={loginPassword}
                        onChange={(e) => setLoginPassword(e.target.value)}
                        disabled={isLoading}
                        className="pl-10 h-12 rounded-xl"
                      />
                    </div>
                  </div>

                  <Button
                    type="submit"
                    size="lg"
                    className="w-full h-12 rounded-xl btn-press shadow-glow"
                    disabled={isLoading}
                  >
                    {isLoading ? (
                      <>
                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        Connexion...
                      </>
                    ) : (
                      <>
                        Se connecter
                        <ArrowRight className="ml-2 h-4 w-4" />
                      </>
                    )}
                  </Button>
                </form>
              )}

              {/* Register Form */}
              {activeTab === "register" && (
                <form onSubmit={handleRegisterSubmit} className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="register-name" className="text-sm font-medium">
                      Nom complet
                    </Label>
                    <div className="relative">
                      <User className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                      <Input
                        id="register-name"
                        type="text"
                        placeholder="Jean Dupont"
                        value={registerName}
                        onChange={(e) => setRegisterName(e.target.value)}
                        disabled={isLoading}
                        className="pl-10 h-12 rounded-xl"
                      />
                    </div>
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="register-email" className="text-sm font-medium">
                      Adresse email
                    </Label>
                    <div className="relative">
                      <Mail className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                      <Input
                        id="register-email"
                        type="email"
                        placeholder="vous@exemple.com"
                        value={registerEmail}
                        onChange={(e) => setRegisterEmail(e.target.value)}
                        disabled={isLoading}
                        className="pl-10 h-12 rounded-xl"
                      />
                    </div>
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="register-password" className="text-sm font-medium">
                      Mot de passe
                    </Label>
                    <div className="relative">
                      <Lock className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                      <Input
                        id="register-password"
                        type="password"
                        placeholder="••••••••"
                        value={registerPassword}
                        onChange={(e) => setRegisterPassword(e.target.value)}
                        disabled={isLoading}
                        className="pl-10 h-12 rounded-xl"
                      />
                    </div>
                    <p className="text-xs text-muted-foreground">
                      Minimum 8 caractères
                    </p>
                  </div>

                  <Button
                    type="submit"
                    size="lg"
                    className="w-full h-12 rounded-xl btn-press shadow-glow"
                    disabled={isLoading}
                  >
                    {isLoading ? (
                      <>
                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        Inscription...
                      </>
                    ) : (
                      <>
                        Créer un compte
                        <ArrowRight className="ml-2 h-4 w-4" />
                      </>
                    )}
                  </Button>
                </form>
              )}

              {/* Magic Link Form */}
              {activeTab === "magic-link" && (
                <>
                  {!magicLinkSent ? (
                    <form onSubmit={handleMagicLinkSubmit} className="space-y-4">
                      <div className="space-y-2">
                        <Label htmlFor="magic-email" className="text-sm font-medium">
                          Adresse email
                        </Label>
                        <div className="relative">
                          <Mail className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                          <Input
                            id="magic-email"
                            type="email"
                            placeholder="vous@exemple.com"
                            value={magicLinkEmail}
                            onChange={(e) => setMagicLinkEmail(e.target.value)}
                            disabled={isLoading}
                            className="pl-10 h-12 rounded-xl"
                          />
                        </div>
                      </div>

                      <Button
                        type="submit"
                        size="lg"
                        className="w-full h-12 rounded-xl btn-press shadow-glow"
                        disabled={isLoading}
                      >
                        {isLoading ? (
                          <>
                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            Envoi...
                          </>
                        ) : (
                          <>
                            Envoyer le lien
                            <ArrowRight className="ml-2 h-4 w-4" />
                          </>
                        )}
                      </Button>
                    </form>
                  ) : (
                    <div className="text-center space-y-4">
                      <div className="rounded-xl bg-secondary/50 p-6">
                        <p className="text-sm text-foreground font-medium mb-2">
                          Lien envoyé !
                        </p>
                        <p className="text-xs text-muted-foreground">
                          Nous avons envoyé un lien de connexion à{" "}
                          <span className="font-medium">{magicLinkEmail}</span>.
                          Cliquez sur le lien dans l'email pour vous connecter.
                        </p>
                      </div>
                      <Button
                        variant="outline"
                        onClick={() => {
                          setMagicLinkSent(false);
                          setMagicLinkEmail("");
                        }}
                        className="rounded-xl"
                      >
                        Envoyer un autre lien
                      </Button>
                    </div>
                  )}
                </>
              )}
            </CardContent>
          </div>
        </Card>

        <p className="text-center text-xs text-muted-foreground mt-6">
          En vous connectant, vous acceptez nos conditions d'utilisation.
        </p>
      </div>
    </div>
  );
};
