import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { authService } from "../services/authService";
import { useNotification } from "../components/NotificationProvider";
import { getApiErrorMessage } from "../services/apiError";

const Login = () => {
  const notification = useNotification();
  const [form, setForm] = useState({ email: "", password: "" });
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const onChange = (e) => {
    setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      await authService.login(form);
      notification.success("Login successful.");
      navigate(authService.getHomePath());
    } catch (err) {
      const message = getApiErrorMessage(err, "Login failed. Check your credentials.");
      notification.error(message);
      if (err?.response?.status === 403 && /verify/i.test(message)) {
        navigate(`/verify-email?from=login&email=${encodeURIComponent(form.email)}`);
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-100 p-4">
      <form onSubmit={handleSubmit} className="w-full max-w-md rounded-xl bg-white p-6 shadow ring-1 ring-slate-200">
        <h1 className="mb-5 text-center text-2xl font-bold text-slate-900">Login</h1>
        <label className="mb-2 block text-sm font-medium text-slate-700">Email</label>
        <input
          required
          type="email"
          name="email"
          value={form.email}
          onChange={onChange}
          className="mb-4 w-full rounded-md border border-slate-300 px-3 py-2 outline-none focus:border-indigo-500"
        />
        <label className="mb-2 block text-sm font-medium text-slate-700">Password</label>
        <div className="relative mb-6">
          <input
            required
            type={showPassword ? "text" : "password"}
            name="password"
            value={form.password}
            onChange={onChange}
            className="w-full rounded-md border border-slate-300 px-3 py-2 pr-14 outline-none focus:border-indigo-500"
          />
          <button
            type="button"
            onClick={() => setShowPassword((prev) => !prev)}
            className="absolute inset-y-0 right-2 my-auto flex h-8 w-8 items-center justify-center rounded text-indigo-600 hover:bg-indigo-50"
            aria-label={showPassword ? "Hide password" : "Show password"}
          >
            {showPassword ? (
              <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M3 3l18 18" />
                <path d="M10.58 10.58A2 2 0 0012 14a2 2 0 001.42-.58" />
                <path d="M9.88 4.24A10.8 10.8 0 0112 4c5 0 9 4.5 10 8a12.34 12.34 0 01-2.1 3.7" />
                <path d="M6.61 6.61C4.68 7.9 3.25 9.87 2 12c1 3.5 5 8 10 8a10.8 10.8 0 004.39-.93" />
              </svg>
            ) : (
              <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
            )}
          </button>
        </div>
        <button
          disabled={loading}
          className="w-full rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-500 disabled:opacity-70"
        >
          {loading ? "Signing in..." : "Sign In"}
        </button>
        <div className="mt-4 flex items-center justify-between text-sm">
          <Link to="/forgot-password" className="text-indigo-600 hover:underline">
            Forgot password?
          </Link>
          <Link to="/register" className="text-indigo-600 hover:underline">
            Create account
          </Link>
        </div>
      </form>
    </div>
  );
};

export default Login;
