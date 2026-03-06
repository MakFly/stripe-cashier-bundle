import { useEffect, useMemo, useRef, useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Badge } from "../components/ui/badge";
import { Button } from "../components/ui/button";
import { Card, CardContent } from "../components/ui/card";
import { Skeleton } from "../components/ui/skeleton";
import { useAuthStore } from "../stores/auth";
import {
  cancelSubscription,
  createSubscriptionCheckoutSession,
  getBillingPortalUrl,
  getCurrentSubscription,
  getSubscriptionPlans,
  resumeSubscription,
} from "../lib/api";
import { formatDate, formatPrice, cn } from "../lib/utils";
import type { SubscriptionPlan } from "../types";
import { Check, ArrowRight, Sparkles, RefreshCw, ShieldCheck, CreditCard, Crown } from "lucide-react";
import { toast } from "sonner";

type BillingCycle = "monthly" | "yearly";

const accentStyles: Record<string, string> = {
  starter: "from-primary/15 via-primary/5 to-transparent",
  pro: "from-accent/20 via-primary/10 to-transparent",
};

export const SubscriptionsPage = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { user, initialized } = useAuthStore();
  const [billingCycle, setBillingCycle] = useState<BillingCycle>("monthly");
  const handledCheckoutStateRef = useRef<string | null>(null);

  const plansQuery = useQuery({
    queryKey: ["subscription-plans"],
    queryFn: getSubscriptionPlans,
  });

  const currentSubscriptionQuery = useQuery({
    queryKey: ["current-subscription"],
    queryFn: getCurrentSubscription,
    enabled: initialized && !!user,
  });

  useEffect(() => {
    const checkoutState = searchParams.get("checkout");
    if (checkoutState !== null && handledCheckoutStateRef.current === checkoutState) {
      return;
    }

    if (checkoutState === "success") {
      handledCheckoutStateRef.current = checkoutState;
      toast.success("Paiement confirmé. Synchronisation de l’abonnement en cours.");
      void currentSubscriptionQuery.refetch();
      return;
    }

    if (checkoutState === "canceled") {
      handledCheckoutStateRef.current = checkoutState;
      toast.message("Souscription annulée.");
    }
  }, [currentSubscriptionQuery, searchParams]);

  const createCheckoutMutation = useMutation({
    mutationFn: ({ planCode, cycle }: { planCode: string; cycle: BillingCycle }) =>
      createSubscriptionCheckoutSession(planCode, cycle),
    onSuccess: (result) => {
      window.location.href = result.checkoutUrl;
    },
    onError: (error: Error) => {
      toast.error(error.message || "Impossible de créer la session de souscription.");
    },
  });

  const billingPortalMutation = useMutation({
    mutationFn: getBillingPortalUrl,
    onSuccess: (result) => {
      window.location.href = result.url;
    },
    onError: (error: Error) => {
      toast.error(error.message || "Impossible d’ouvrir le portail de facturation.");
    },
  });

  const cancelMutation = useMutation({
    mutationFn: cancelSubscription,
    onSuccess: async () => {
      toast.success("Abonnement résilié en fin de période.");
      await queryClient.invalidateQueries({ queryKey: ["current-subscription"] });
    },
    onError: (error: Error) => {
      toast.error(error.message || "Impossible de résilier l’abonnement.");
    },
  });

  const resumeMutation = useMutation({
    mutationFn: resumeSubscription,
    onSuccess: async () => {
      toast.success("Abonnement réactivé.");
      await queryClient.invalidateQueries({ queryKey: ["current-subscription"] });
    },
    onError: (error: Error) => {
      toast.error(error.message || "Impossible de reprendre l’abonnement.");
    },
  });

  const plans = useMemo(
    () => plansQuery.data?.member ?? [],
    [plansQuery.data],
  );

  const currentSubscription = currentSubscriptionQuery.data?.subscription ?? null;
  const currentPlanCode = currentSubscription?.plan?.code ?? null;
  const currentBillingCycle = currentSubscription?.plan?.billingCycle ?? null;

  const handleSubscribe = (plan: SubscriptionPlan) => {
    if (!user) {
      navigate("/login?redirect=/subscriptions", { viewTransition: true });
      return;
    }

    createCheckoutMutation.mutate({ planCode: plan.code, cycle: billingCycle });
  };

  const isCurrentPlan = (plan: SubscriptionPlan) =>
    currentPlanCode === plan.code && currentBillingCycle === billingCycle;

  return (
    <div className="max-w-6xl mx-auto">
      <section className="relative overflow-hidden rounded-[2rem] border border-border/50 bg-card/60 px-6 py-8 shadow-soft md:px-10 md:py-12">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(34,197,94,0.12),transparent_35%),radial-gradient(circle_at_bottom_right,rgba(234,179,8,0.12),transparent_30%)]" />
        <div className="absolute right-6 top-6 h-20 w-20 rounded-full border border-primary/20 bg-background/50 blur-2xl" />
        <div className="relative grid gap-10 lg:grid-cols-[1.2fr_0.8fr] lg:items-end">
          <div className="space-y-5">
            <Badge className="rounded-full bg-primary/10 px-4 py-1 text-primary hover:bg-primary/10">
              Facturation récurrente
            </Badge>
            <div className="space-y-3">
              <h1
                className="max-w-3xl text-4xl font-medium leading-tight md:text-5xl"
                style={{ fontFamily: "'Newsreader', serif" }}
              >
                Un tunnel abonnement propre, lisible et branché Stripe jusqu’à la facture.
              </h1>
              <p className="max-w-2xl text-base leading-relaxed text-muted-foreground md:text-lg">
                Deux offres, un cycle mensuel ou annuel, une réduction annuelle claire et
                une gestion d’abonnement accessible depuis votre espace sans détour.
              </p>
            </div>
            <div className="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
              <span className="rounded-full border border-border/60 px-3 py-1">Checkout Stripe hébergé</span>
              <span className="rounded-full border border-border/60 px-3 py-1">Webhooks source de vérité</span>
              <span className="rounded-full border border-border/60 px-3 py-1">Factures archivées</span>
            </div>
          </div>

          <div className="justify-self-start rounded-[1.5rem] border border-border/60 bg-background/80 p-2 shadow-soft">
            <div className="grid grid-cols-2 gap-2">
              {(["monthly", "yearly"] as const).map((cycle) => (
                <button
                  key={cycle}
                  type="button"
                  onClick={() => setBillingCycle(cycle)}
                  className={cn(
                    "rounded-[1.15rem] px-5 py-3 text-sm font-medium transition-all duration-300",
                    billingCycle === cycle
                      ? "bg-primary text-primary-foreground shadow-glow"
                      : "text-muted-foreground hover:bg-secondary hover:text-foreground",
                  )}
                >
                  {cycle === "monthly" ? "Mensuel" : "Annuel -20%"}
                </button>
              ))}
            </div>
          </div>
        </div>
      </section>

      {user ? (
        <section className="mt-8">
          <Card className="overflow-hidden border-0 shadow-soft">
            <CardContent className="grid gap-6 p-6 md:grid-cols-[1fr_auto] md:items-center">
              {currentSubscriptionQuery.isLoading ? (
                <div className="space-y-3">
                  <Skeleton className="h-5 w-36 rounded-full" />
                  <Skeleton className="h-10 w-72 rounded-xl" />
                  <Skeleton className="h-4 w-56 rounded-full" />
                </div>
              ) : currentSubscriptionQuery.data?.hasSubscription && currentSubscription ? (
                <>
                  <div className="space-y-3">
                    <div className="flex flex-wrap items-center gap-3">
                      <Badge className="rounded-full bg-primary/10 px-3 py-1 text-primary hover:bg-primary/10">
                        Mon abonnement
                      </Badge>
                      <Badge variant="outline" className="rounded-full px-3 py-1">
                        {currentSubscription.plan?.name ?? "Plan actif"}
                      </Badge>
                      <Badge variant="secondary" className="rounded-full px-3 py-1">
                        {currentSubscription.stripeStatus}
                      </Badge>
                    </div>
                    <div>
                      <h2
                        className="text-2xl font-medium"
                        style={{ fontFamily: "'Newsreader', serif" }}
                      >
                        {currentSubscription.plan?.name ?? "Abonnement"} {currentSubscription.plan?.billingCycle === "yearly" ? "annuel" : "mensuel"}
                      </h2>
                      <p className="mt-1 text-muted-foreground">
                        {currentSubscription.isOnTrial && currentSubscription.trialEndsAt
                          ? `Période d’essai jusqu’au ${formatDate(currentSubscription.trialEndsAt)}`
                          : currentSubscription.endsAt
                            ? `Fin prévue le ${formatDate(currentSubscription.endsAt)}`
                            : "Abonnement actif et synchronisé avec Stripe."}
                      </p>
                    </div>
                  </div>

                  <div className="flex flex-wrap gap-3 md:justify-end">
                    <Button
                      type="button"
                      variant="outline"
                      className="rounded-xl"
                      onClick={() => billingPortalMutation.mutate()}
                      disabled={billingPortalMutation.isPending}
                    >
                      <CreditCard className="mr-2 h-4 w-4" />
                      Gérer la facturation
                    </Button>
                    {currentSubscription.canResume ? (
                      <Button
                        type="button"
                        className="rounded-xl"
                        onClick={() => resumeMutation.mutate()}
                        disabled={resumeMutation.isPending}
                      >
                        <RefreshCw className="mr-2 h-4 w-4" />
                        Reprendre
                      </Button>
                    ) : null}
                    {currentSubscription.canCancel ? (
                      <Button
                        type="button"
                        variant="destructive"
                        className="rounded-xl"
                        onClick={() => cancelMutation.mutate()}
                        disabled={cancelMutation.isPending}
                      >
                        Résilier
                      </Button>
                    ) : null}
                  </div>
                </>
              ) : (
                <>
                  <div className="space-y-2">
                    <Badge className="rounded-full bg-secondary px-3 py-1 text-foreground hover:bg-secondary">
                      Aucun abonnement actif
                    </Badge>
                    <h2
                      className="text-2xl font-medium"
                      style={{ fontFamily: "'Newsreader', serif" }}
                    >
                      Choisissez un plan quand vous êtes prêt.
                    </h2>
                    <p className="text-muted-foreground">
                      Le checkout créera une session Stripe dédiée avec synchronisation webhook côté API.
                    </p>
                  </div>
                  <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <ShieldCheck className="h-4 w-4 text-primary" />
                    Facturation et portail gérés côté Stripe
                  </div>
                </>
              )}
            </CardContent>
          </Card>
        </section>
      ) : null}

      <section className="mt-10 grid gap-6 lg:grid-cols-2">
        {plansQuery.isLoading
          ? Array.from({ length: 2 }).map((_, index) => (
              <Card key={index} className="overflow-hidden rounded-[2rem] border-0 shadow-soft">
                <CardContent className="space-y-5 p-8">
                  <Skeleton className="h-6 w-24 rounded-full" />
                  <Skeleton className="h-12 w-40 rounded-2xl" />
                  <Skeleton className="h-5 w-64 rounded-full" />
                  <Skeleton className="h-36 w-full rounded-[1.5rem]" />
                </CardContent>
              </Card>
            ))
          : plans.map((plan) => {
              const selectedPrice = plan[billingCycle];
              const annualEquivalent = billingCycle === "yearly" ? plan.yearly.monthlyEquivalent : null;
              const highlighted = plan.code === "pro";

              return (
                <Card
                  key={plan.code}
                  className={cn(
                    "group relative overflow-hidden rounded-[2rem] border-0 shadow-soft transition-transform duration-300 hover:-translate-y-1",
                    highlighted && "shadow-elevated",
                  )}
                >
                  <div className={cn("absolute inset-0 bg-gradient-to-br", accentStyles[plan.code] ?? accentStyles.starter)} />
                  <CardContent className="relative flex h-full flex-col p-8">
                    <div className="flex items-start justify-between gap-4">
                      <div className="space-y-3">
                        <div className="flex items-center gap-3">
                          <Badge
                            className={cn(
                              "rounded-full px-3 py-1",
                              highlighted
                                ? "bg-accent text-accent-foreground hover:bg-accent"
                                : "bg-primary/10 text-primary hover:bg-primary/10",
                            )}
                          >
                            {plan.name}
                          </Badge>
                          {billingCycle === "yearly" ? (
                            <Badge variant="outline" className="rounded-full px-3 py-1">
                              -{plan.yearlyDiscountPercent}%
                            </Badge>
                          ) : null}
                        </div>
                        <div>
                          <h2
                            className="text-3xl font-medium"
                            style={{ fontFamily: "'Newsreader', serif" }}
                          >
                            {plan.name === "Pro" ? (
                              <span className="inline-flex items-center gap-2">
                                <Crown className="h-6 w-6 text-accent" />
                                {plan.name}
                              </span>
                            ) : (
                              plan.name
                            )}
                          </h2>
                          <p className="mt-2 max-w-md text-muted-foreground">{plan.description}</p>
                        </div>
                      </div>
                      <Sparkles className={cn("h-6 w-6 transition-transform duration-300 group-hover:rotate-12", highlighted ? "text-accent" : "text-primary")} />
                    </div>

                    <div className="mt-8 rounded-[1.5rem] border border-border/60 bg-background/80 p-6">
                      <div className="flex flex-wrap items-baseline gap-3">
                        <div
                          className="text-5xl font-medium text-foreground"
                          style={{ fontFamily: "'Newsreader', serif" }}
                        >
                          {formatPrice(selectedPrice.amount)}
                        </div>
                        <span className="text-sm uppercase tracking-[0.2em] text-muted-foreground">
                          {billingCycle === "monthly" ? "/ mois" : "/ an"}
                        </span>
                      </div>
                      <div className="mt-3 space-y-1 text-sm text-muted-foreground">
                        {plan.trialDays > 0 ? (
                          <p>{plan.trialDays} jours gratuits avant facturation.</p>
                        ) : (
                          <p>Accès immédiat sans période d’essai.</p>
                        )}
                        {typeof annualEquivalent === "number" ? (
                          <p>Équivalent {formatPrice(annualEquivalent)} / mois, facturé annuellement.</p>
                        ) : (
                          <p>Facturation mensuelle sans engagement annuel.</p>
                        )}
                      </div>
                    </div>

                    <ul className="mt-8 space-y-3 text-sm text-foreground/90">
                      {plan.features.map((feature) => (
                        <li key={feature} className="flex items-start gap-3">
                          <span className="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <Check className="h-4 w-4" />
                          </span>
                          <span>{feature}</span>
                        </li>
                      ))}
                    </ul>

                    <div className="mt-8 flex flex-col gap-3 pt-4">
                      <Button
                        type="button"
                        size="lg"
                        className={cn(
                          "h-12 rounded-xl",
                          highlighted ? "shadow-glow" : "btn-press",
                        )}
                        onClick={() => handleSubscribe(plan)}
                        disabled={createCheckoutMutation.isPending || isCurrentPlan(plan)}
                      >
                        {isCurrentPlan(plan)
                          ? "Plan déjà actif"
                          : user
                            ? "Souscrire maintenant"
                            : "Se connecter pour souscrire"}
                        <ArrowRight className="ml-2 h-4 w-4" />
                      </Button>
                      <p className="text-xs text-muted-foreground">
                        {user
                          ? "Le checkout Stripe s’ouvre dans la foulée. Les webhooks confirment ensuite l’état local."
                          : "La page reste publique, mais le checkout requiert une session authentifiée."}
                      </p>
                    </div>
                  </CardContent>
                </Card>
              );
            })}
      </section>

      {!user ? (
        <section className="mt-8 rounded-[1.75rem] border border-dashed border-border/70 bg-background/60 px-6 py-6 text-sm text-muted-foreground">
          <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <p>
              Vous pouvez comparer les offres librement. La création de session Stripe ne s’active qu’après connexion.
            </p>
            <Link to="/login?redirect=/subscriptions" viewTransition>
              <Button variant="outline" className="rounded-xl">
                Connexion
              </Button>
            </Link>
          </div>
        </section>
      ) : null}
    </div>
  );
};
