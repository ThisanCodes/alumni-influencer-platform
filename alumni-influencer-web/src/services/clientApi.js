import axios from "axios";

const clientApi = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api",
});

clientApi.interceptors.request.use((config) => {
  const apiKey = import.meta.env.VITE_ANALYTICS_API_KEY;

  if (apiKey && !config.headers["X-API-Key"]) {
    config.headers["X-API-Key"] = apiKey;
  }

  return config;
});

export default clientApi;
