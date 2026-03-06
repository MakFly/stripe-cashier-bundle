import { useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { useAuthStore } from "../../stores/auth";
import { Skeleton } from "../ui/skeleton";

interface ProtectedRouteProps {
  children: React.ReactNode;
}

export const ProtectedRoute = ({ children }: ProtectedRouteProps) => {
  const navigate = useNavigate();
  const { user, isLoading, initialized } = useAuthStore();

  useEffect(() => {
    if (initialized && !isLoading && !user) {
      navigate("/login", { replace: true });
    }
  }, [initialized, user, isLoading, navigate]);

  if (!initialized || isLoading) {
    return (
      <div className="min-h-screen space-y-4 p-6 md:p-10">
        <Skeleton className="h-10 w-64 rounded-xl" />
        <Skeleton className="h-28 w-full rounded-2xl" />
        <Skeleton className="h-28 w-full rounded-2xl" />
        <Skeleton className="h-28 w-3/4 rounded-2xl" />
      </div>
    );
  }

  if (!user) {
    return null;
  }

  return <>{children}</>;
};
