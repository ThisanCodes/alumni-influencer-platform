export const getApiErrorMessage = (err, fallback = "Something went wrong.") => {
  const data = err?.response?.data;
  const details = data?.messages || data?.errors;

  if (details && typeof details === "object") {
    return Object.values(details).filter(Boolean).join(" ");
  }

  return data?.message || data?.error || fallback;
};
