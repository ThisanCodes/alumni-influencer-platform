import { useState } from "react";
import { Link } from "react-router-dom";
import { authService } from "../services/authService";
import { useNotification } from "../components/NotificationProvider";
import { getApiErrorMessage } from "../services/apiError";

const ForgotPassword = () => {
  const notification = useNotification();
  const [email, setEmail] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const res = await authService.forgotPassword({ email });
      const nextSuccess = res?.message || "Password reset instructions sent.";
      notification.success(nextSuccess);
    } catch (err) {
      const nextError = getApiErrorMessage(err, "Failed to request password reset.");
      notification.error(nextError);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-100 p-4">
      <form onSubmit={handleSubmit} className="w-full max-w-md rounded-xl bg-white p-6 shadow ring-1 ring-slate-200">
        <h1 className="mb-2 text-2xl font-bold text-slate-900">Forgot Password</h1>
        <p className="mb-5 text-sm text-slate-600">Enter your account email to request a reset token.</p>

        <label className="mb-2 block text-sm font-medium text-slate-700">Email</label>
        <input
          required
          type="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          className="mb-6 w-full rounded-md border border-slate-300 px-3 py-2 outline-none focus:border-indigo-500"
        />

        <button
          disabled={loading}
          className="w-full rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-500 disabled:opacity-70"
        >
          {loading ? "Submitting..." : "Request Reset"}
        </button>

        <div className="mt-4 flex justify-between text-sm">
          <Link to="/login" className="text-indigo-600 hover:underline">
            Back to login
          </Link>
          <Link to="/reset-password" className="text-indigo-600 hover:underline">
            Have reset token?
          </Link>
        </div>
      </form>
    </div>
  );
};

export default ForgotPassword;
