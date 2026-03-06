import { useEffect, useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import { AlertCircle, Loader2, RefreshCw } from "lucide-react";
import { Button } from "../components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { useAuthStore } from "../stores/auth";

const verificationByToken = new Map<string, Promise<boolean>>();
const MIN_LOADING_MS = 500;

const sleep = (ms: number) =>
  new Promise<void>((resolve) => {
    setTimeout(resolve, ms);
  });

function verifyTokenOnce(
  token: string,
  verifyMagicLink: (token: string) => Promise<void>,
): Promise<boolean> {
  const existing = verificationByToken.get(token);
  if (existing) {
    return existing;
  }

  const task = verifyMagicLink(token)
    .then(() => true)
    .catch(() => false)
    .finally(() => {
      verificationByToken.delete(token);
    });

  verificationByToken.set(token, task);
  return task;
}

export const MagicLinkVerifyPage = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const verifyMagicLink = useAuthStore((state) => state.verifyMagicLink);
  const [status, setStatus] = useState<"loading" | "error">("loading");
  const [errorMessage, setErrorMessage] = useState("Lien invalide ou expiré.");

  useEffect(() => {
    const startedAt = Date.now();
    const token = searchParams.get("token");
    if (!token) {
      void (async () => {
        const elapsed = Date.now() - startedAt;
        const remaining = Math.max(0, MIN_LOADING_MS - elapsed);
        if (remaining > 0) {
          await sleep(remaining);
        }
        setStatus("error");
        setErrorMessage("Lien de connexion invalide: token manquant.");
      })();
      return;
    }

    let cancelled = false;

    const verify = async () => {
      const success = await verifyTokenOnce(token, (value) =>
        verifyMagicLink(value, true),
      );
      const elapsed = Date.now() - startedAt;
      const remaining = Math.max(0, MIN_LOADING_MS - elapsed);
      if (remaining > 0) {
        await sleep(remaining);
      }
      if (cancelled) {
        return;
      }

      if (success) {
        navigate("/", { replace: true });
      } else {
        setStatus("error");
        setErrorMessage("Ce lien est invalide ou expiré. Demande un nouveau lien.");
      }
    };

    void verify();

    return () => {
      cancelled = true;
    };
  }, [navigate, searchParams, verifyMagicLink]);

  return (
    <div className="min-h-[calc(100vh-8rem)] flex items-center justify-center px-4">
      {status === "loading" ? (
        <div className="text-center space-y-4">
          <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-primary/10">
            <Loader2 className="h-6 w-6 animate-spin text-primary" />
          </div>
          <h1 className="text-xl font-medium">Connexion en cours...</h1>
          <p className="text-muted-foreground text-sm">
            Vérification du lien magique
          </p>
        </div>
      ) : (
        <Card className="w-full max-w-md border-destructive/30">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-destructive">
              <AlertCircle className="h-5 w-5" />
              Lien invalide
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <p className="text-sm text-muted-foreground">{errorMessage}</p>
            <Button asChild className="w-full">
              <Link to="/login?tab=magic-link" viewTransition>
                <RefreshCw className="mr-2 h-4 w-4" />
                Recommencer
              </Link>
            </Button>
          </CardContent>
        </Card>
      )}
    </div>
  );
};
