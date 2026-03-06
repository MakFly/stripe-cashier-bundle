import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { setup2FA, verify2FASetup } from "../lib/api";
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
import { Shield, Key, Copy, Check, Loader2, ArrowRight } from "lucide-react";
import { toast } from "sonner";

export const TwoFactorSetupPage = () => {
  const navigate = useNavigate();
  const [step, setStep] = useState<"setup" | "verify" | "done">("setup");
  const [isLoading, setIsLoading] = useState(false);
  const [qrCodeUrl, setQrCodeUrl] = useState("");
  const [secret, setSecret] = useState("");
  const [recoveryCodes, setRecoveryCodes] = useState<string[]>([]);
  const [copiedSecret, setCopiedSecret] = useState(false);
  const [otpCode, setOtpCode] = useState("");
  const [error, setError] = useState("");

  const handleSetup = async () => {
    setIsLoading(true);
    setError("");
    try {
      const data = await setup2FA();
      setQrCodeUrl(data.qrCode);
      setSecret(data.secret);
      setRecoveryCodes(data.backupCodes);
      setStep("verify");
    } catch (err) {
      setError("Erreur lors de la configuration. Veuillez réessayer.");
    } finally {
      setIsLoading(false);
    }
  };

  const handleVerify = async () => {
    if (otpCode.length !== 6) {
      setError("Veuillez entrer le code à 6 chiffres");
      return;
    }

    setIsLoading(true);
    setError("");
    try {
      await verify2FASetup(otpCode, secret);
      setStep("done");
      toast.success("Authentification à deux facteurs activée !");
    } catch (err) {
      setError("Code incorrect. Veuillez réessayer.");
    } finally {
      setIsLoading(false);
    }
  };

  const copySecret = () => {
    navigator.clipboard.writeText(secret);
    setCopiedSecret(true);
    setTimeout(() => setCopiedSecret(false), 2000);
  };

  const copyRecoveryCodes = () => {
    navigator.clipboard.writeText(recoveryCodes.join("\n"));
    toast.success("Codes de récupération copiés !");
  };

  return (
    <div className="min-h-[calc(100vh-8rem)] flex items-center justify-center px-4 py-12">
      <div className="absolute inset-0 overflow-hidden pointer-events-none">
        <div className="absolute top-1/4 left-1/4 w-96 h-96 rounded-full bg-primary/5 blur-3xl" />
        <div className="absolute bottom-1/4 right-1/4 w-96 h-96 rounded-full bg-accent/5 blur-3xl" />
      </div>

      <div className="relative w-full max-w-lg animate-fade-up">
        {/* Header */}
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
            Sécuriser votre compte
          </p>
        </div>

        <Card className="border-0 shadow-elevated overflow-hidden">
          <div className="bg-gradient-to-br from-primary/5 via-transparent to-accent/5">
            {/* Step 1: Introduction */}
            {step === "setup" && (
              <>
                <CardHeader className="text-center pb-2">
                  <CardTitle
                    className="text-2xl"
                    style={{ fontFamily: "'Newsreader', serif" }}
                  >
                    Authentification à deux facteurs
                  </CardTitle>
                  <CardDescription>
                    Ajoutez une couche de sécurité supplémentaire à votre compte
                  </CardDescription>
                </CardHeader>
                <CardContent className="pt-4">
                  <div className="space-y-6">
                    <div className="rounded-xl bg-secondary/50 p-6 space-y-4">
                      <div className="flex items-start gap-3">
                        <div className="rounded-full bg-primary/10 p-2 mt-0.5">
                          <Shield className="h-4 w-4 text-primary" />
                        </div>
                        <div>
                          <p className="text-sm font-medium">Sécurité renforcée</p>
                          <p className="text-xs text-muted-foreground">
                            Même si quelqu'un connaît votre mot de passe, il ne
                            pourra pas accéder à votre compte sans votre téléphone.
                          </p>
                        </div>
                      </div>

                      <div className="flex items-start gap-3">
                        <div className="rounded-full bg-primary/10 p-2 mt-0.5">
                          <Key className="h-4 w-4 text-primary" />
                        </div>
                        <div>
                          <p className="text-sm font-medium">Codes de récupération</p>
                          <p className="text-xs text-muted-foreground">
                            Si vous perdez votre téléphone, vous pouvez utiliser les
                            codes de récupération pour accéder à votre compte.
                          </p>
                        </div>
                      </div>
                    </div>

                    {error && (
                      <div className="rounded-xl bg-destructive/10 border border-destructive/20 p-4">
                        <p className="text-sm text-destructive font-medium">
                          {error}
                        </p>
                      </div>
                    )}

                    <Button
                      onClick={handleSetup}
                      size="lg"
                      className="w-full h-12 rounded-xl btn-press shadow-glow"
                      disabled={isLoading}
                    >
                      {isLoading ? (
                        <>
                          <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                          Configuration...
                        </>
                      ) : (
                        <>
                          Commencer la configuration
                          <ArrowRight className="ml-2 h-4 w-4" />
                        </>
                      )}
                    </Button>

                    <Button
                      variant="ghost"
                      onClick={() => navigate("/")}
                      className="w-full"
                    >
                      Plus tard
                    </Button>
                  </div>
                </CardContent>
              </>
            )}

            {/* Step 2: Scan QR Code */}
            {step === "verify" && (
              <>
                <CardHeader className="text-center pb-2">
                  <CardTitle
                    className="text-2xl"
                    style={{ fontFamily: "'Newsreader', serif" }}
                  >
                    Scanner le QR code
                  </CardTitle>
                  <CardDescription>
                    Utilisez votre application d'authentification pour scanner le
                    QR code
                  </CardDescription>
                </CardHeader>
                <CardContent className="pt-4">
                  <div className="space-y-6">
                    {/* QR Code */}
                    <div className="flex justify-center">
                      <div className="rounded-xl bg-white p-4 shadow-sm">
                        <img
                          src={qrCodeUrl}
                          alt="QR Code pour 2FA"
                          className="w-48 h-48"
                        />
                      </div>
                    </div>

                    {/* Secret Key */}
                    <div className="space-y-2">
                      <Label className="text-sm font-medium">
                        Clé secrète (si vous ne pouvez pas scanner)
                      </Label>
                      <div className="flex gap-2">
                        <Input
                          value={secret}
                          readOnly
                          className="font-mono text-sm rounded-xl"
                        />
                        <Button
                          variant="outline"
                          size="icon"
                          onClick={copySecret}
                          className="rounded-xl shrink-0"
                        >
                          {copiedSecret ? (
                            <Check className="h-4 w-4 text-green-500" />
                          ) : (
                            <Copy className="h-4 w-4" />
                          )}
                        </Button>
                      </div>
                    </div>

                    {/* Verification Code */}
                    <div className="space-y-2">
                      <Label className="text-sm font-medium text-center block">
                        Entrez le code de vérification
                      </Label>
                      <OtpInput
                        value={otpCode}
                        onChange={setOtpCode}
                        disabled={isLoading}
                        autoFocus
                      />
                    </div>

                    {error && (
                      <div className="rounded-xl bg-destructive/10 border border-destructive/20 p-4">
                        <p className="text-sm text-destructive font-medium">
                          {error}
                        </p>
                      </div>
                    )}

                    <Button
                      onClick={handleVerify}
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
                          Vérifier et activer
                          <ArrowRight className="ml-2 h-4 w-4" />
                        </>
                      )}
                    </Button>

                    <Button
                      variant="ghost"
                      onClick={() => setStep("setup")}
                      className="w-full"
                    >
                      Annuler
                    </Button>
                  </div>
                </CardContent>
              </>
            )}

            {/* Step 3: Success & Recovery Codes */}
            {step === "done" && (
              <>
                <CardHeader className="text-center pb-2">
                  <CardTitle
                    className="text-2xl"
                    style={{ fontFamily: "'Newsreader', serif" }}
                  >
                    2FA activée !
                  </CardTitle>
                  <CardDescription>
                    Sauvegardez vos codes de récupération
                  </CardDescription>
                </CardHeader>
                <CardContent className="pt-4">
                  <div className="space-y-6">
                    <div className="rounded-xl bg-green-500/10 border border-green-500/20 p-4">
                      <p className="text-sm text-green-600 dark:text-green-400 font-medium text-center">
                        L'authentification à deux facteurs est maintenant activée
                        sur votre compte.
                      </p>
                    </div>

                    {/* Recovery Codes */}
                    <div className="space-y-2">
                      <Label className="text-sm font-medium">
                        Codes de récupération
                      </Label>
                      <p className="text-xs text-muted-foreground">
                        Sauvegardez ces codes dans un endroit sûr. Vous pourrez
                        les utiliser pour accéder à votre compte si vous perdez
                        votre téléphone.
                      </p>
                      <div className="rounded-xl bg-secondary/50 p-4 space-y-1">
                        {recoveryCodes.map((code, index) => (
                          <div
                            key={index}
                            className="font-mono text-sm text-center"
                          >
                            {code}
                          </div>
                        ))}
                      </div>
                      <Button
                        variant="outline"
                        onClick={copyRecoveryCodes}
                        className="w-full rounded-xl"
                      >
                        <Copy className="mr-2 h-4 w-4" />
                        Copier les codes
                      </Button>
                    </div>

                    <div className="rounded-xl bg-amber-500/10 border border-amber-500/20 p-4">
                      <p className="text-xs text-amber-600 dark:text-amber-400">
                        <span className="font-medium">Important :</span> Ces
                        codes ne seront affichés qu'une seule fois. Assurez-vous
                        de les sauvegarder maintenant.
                      </p>
                    </div>

                    <Button
                      onClick={() => navigate("/")}
                      size="lg"
                      className="w-full h-12 rounded-xl btn-press shadow-glow"
                    >
                      Terminer
                    </Button>
                  </div>
                </CardContent>
              </>
            )}
          </div>
        </Card>
      </div>
    </div>
  );
};
