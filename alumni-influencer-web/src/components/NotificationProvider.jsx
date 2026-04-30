import { createContext, useContext, useMemo, useState } from "react";

const NotificationContext = createContext(null);

export const NotificationProvider = ({ children }) => {
  const [notifications, setNotifications] = useState([]);

  const removeNotification = (id) => {
    setNotifications((prev) => prev.filter((item) => item.id !== id));
  };

  const notify = (type, message) => {
    const id = Date.now() + Math.random();
    setNotifications((prev) => [...prev, { id, type, message }]);
    setTimeout(() => removeNotification(id), 5000);
  };

  const value = useMemo(
    () => ({
      success: (message) => notify("success", message),
      error: (message) => notify("error", message),
      info: (message) => notify("info", message),
    }),
    []
  );

  return (
    <NotificationContext.Provider value={value}>
      {children}
      <div className="fixed right-4 top-4 z-50 flex w-[calc(100%-2rem)] max-w-sm flex-col gap-3">
        {notifications.map((notification) => (
          <div
            key={notification.id}
            className={`rounded-lg p-4 text-sm font-medium shadow-lg ring-1 ${
              notification.type === "success"
                ? "bg-green-50 text-green-800 ring-green-200"
                : notification.type === "error"
                ? "bg-red-50 text-red-800 ring-red-200"
                : "bg-blue-50 text-blue-800 ring-blue-200"
            }`}
          >
            <div className="flex items-start justify-between gap-3">
              <span>{notification.message}</span>
              <button
                type="button"
                onClick={() => removeNotification(notification.id)}
                className="text-lg leading-none opacity-70 hover:opacity-100"
                aria-label="Dismiss notification"
              >
                &times;
              </button>
            </div>
          </div>
        ))}
      </div>
    </NotificationContext.Provider>
  );
};

export const useNotification = () => {
  const context = useContext(NotificationContext);

  if (!context) {
    throw new Error("useNotification must be used inside NotificationProvider");
  }

  return context;
};
