import api from "./api";

const homeByRole = {
  university: "/dashboard",
  alumni: "/my-profile",
};

export const authService = {
  login: async (payload) => {
    const { data } = await api.post("/auth/login", payload);
    const token = data?.data?.tokens?.access_token || data?.token;
    const user = data?.data?.user || data?.user;

    if (token) {
      localStorage.setItem("token", token);
    }
    if (user) {
      localStorage.setItem("user", JSON.stringify(user));
    }
    return data;
  },
  register: async (payload) => {
    const { data } = await api.post("/auth/register", payload);
    return data;
  },
  forgotPassword: async (payload) => {
    const { data } = await api.post("/auth/forgot-password", payload);
    return data;
  },
  resetPassword: async (payload) => {
    const { data } = await api.post("/auth/reset-password", {
      token: payload.token,
      new_password: payload.password,
    });
    return data;
  },
  verifyEmail: async (token) => {
    const { data } = await api.get("/auth/verify-email", {
      params: { token },
    });
    return data;
  },
  logout: async () => {
    try {
      await api.post("/auth/logout");
    } finally {
      localStorage.removeItem("token");
      localStorage.removeItem("user");
    }
  },
  getCurrentUser: () => {
    const rawUser = localStorage.getItem("user");
    if (!rawUser) return null;
    try {
      return JSON.parse(rawUser);
    } catch {
      return null;
    }
  },
  getCurrentRole: () => authService.getCurrentUser()?.role || "alumni",
  getHomePath: (role = authService.getCurrentRole()) => homeByRole[role] || "/my-profile",
  isAuthenticated: () => Boolean(localStorage.getItem("token")),
};
