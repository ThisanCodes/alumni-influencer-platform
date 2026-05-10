import { useEffect, useState } from "react";
import { Link, useSearchParams } from "react-router-dom";
import { authService } from "../services/authService";
import { useNotification } from "../components/NotificationProvider";
import { getApiErrorMessage } from "../services/apiError";

const VerifyEmail = () => {
  const notification = useNotification();
  const [searchParams] = useSearchParams();
  const token = searchParams.get("token") || "";
  const email = searchParams.get("email") || "";
  const fromLogin = searchParams.get("from") === "login";

  const [status, setStatus] = useState(token ? "loading" : "idle");
  const [message, setMessage] = useState("");

  useEffect(() => {
    const verify = async () => {
      if (!token) return;
      setStatus("loading");
      setMessage("");
      try {
        const res = await authService.verifyEmail(token);
        const nextMessage = res?.message || "Email verified successfully. You can now log in.";
        setStatus("success");
        setMessage(nextMessage);
        notification.success(nextMessage);
      } catch (err) {
        const nextError = getApiErrorMessage(err, "Invalid or expired verification token.");
        setStatus("error");
        setMessage(nextError);
        notification.error(nextError);
      }
    };
    verify();
  }, [token]);

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-100 p-4">
      <div className="w-full max-w-md rounded-xl bg-white p-6 shadow ring-1 ring-slate-200">
        <h1 className="mb-4 text-2xl font-bold text-slate-900">Verify Email</h1>

        {status === "loading" ? <p className="rounded bg-slate-50 p-3 text-sm text-slate-700">Verifying your email...</p> : null}
        {status === "success" ? <p className="rounded bg-green-50 p-3 text-sm text-green-700">{message}</p> : null}
        {status === "error" ? <p className="rounded bg-red-50 p-3 text-sm text-red-700">{message}</p> : null}

        {status === "idle" ? (
          <div className="space-y-3 rounded bg-indigo-50 p-4 text-sm text-indigo-900">
            <p>{fromLogin ? "Your account is not verified yet." : "Registration successful. Please verify your email."}</p>
            <p>
              Check your inbox{email ? ` for ${email}` : ""} and click the verification link sent by the backend.
            </p>
          </div>
        ) : null}

        <div className="mt-5">
          <Link to="/login" className="text-sm font-medium text-indigo-600 hover:underline">
            Back to login
          </Link>
        </div>
      </div>
    </div>
  );
};

export default VerifyEmail;
