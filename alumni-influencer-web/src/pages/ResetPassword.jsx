import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { authService } from "../services/authService";
import { useNotification } from "../components/NotificationProvider";
import { getApiErrorMessage } from "../services/apiError";

const ResetPassword = () => {
  const notification = useNotification();
  const [form, setForm] = useState({ token: "", password: "" });
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const onChange = (e) => {
    setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const res = await authService.resetPassword(form);
      const nextSuccess = res?.message || "Password reset successful.";
      notification.success(nextSuccess);
      setTimeout(() => navigate("/login"), 1000);
    } catch (err) {
      const nextError = getApiErrorMessage(err, "Failed to reset password.");
      notification.error(nextError);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-100 p-4">
      <form onSubmit={handleSubmit} className="w-full max-w-md rounded-xl bg-white p-6 shadow ring-1 ring-slate-200">
        <h1 className="mb-2 text-2xl font-bold text-slate-900">Reset Password</h1>
        <p className="mb-5 text-sm text-slate-600">Use the reset token from your email and set a new password.</p>

        <label className="mb-2 block text-sm font-medium text-slate-700">Reset Token</label>
        <input
          required
          name="token"
          value={form.token}
          onChange={onChange}
          className="mb-4 w-full rounded-md border border-slate-300 px-3 py-2 outline-none focus:border-indigo-500"
        />

        <label className="mb-2 block text-sm font-medium text-slate-700">New Password</label>
        <input
          required
          type="password"
          name="password"
          value={form.password}
          onChange={onChange}
          className="mb-6 w-full rounded-md border border-slate-300 px-3 py-2 outline-none focus:border-indigo-500"
        />

        <button
          disabled={loading}
          className="w-full rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-500 disabled:opacity-70"
        >
          {loading ? "Resetting..." : "Reset Password"}
        </button>

        <p className="mt-4 text-sm text-slate-600">
          Back to{" "}
          <Link to="/login" className="text-indigo-600 hover:underline">
            Login
          </Link>
        </p>
      </form>
    </div>
  );
};

export default ResetPassword;
