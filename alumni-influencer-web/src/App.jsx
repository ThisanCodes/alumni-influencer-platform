import { Navigate, Route, Routes } from "react-router-dom";
import Sidebar from "./components/Sidebar";
import Navbar from "./components/Navbar";
import Login from "./pages/Login";
import Register from "./pages/Register";
import ForgotPassword from "./pages/ForgotPassword";
import ResetPassword from "./pages/ResetPassword";
import VerifyEmail from "./pages/VerifyEmail";
import Dashboard from "./pages/Dashboard";
import Alumni from "./pages/Alumni";
import ApiKeys from "./pages/ApiKeys";
import MyProfile from "./pages/MyProfile";
import Bidding from "./pages/Bidding";
import { authService } from "./services/authService";
import { NotificationProvider } from "./components/NotificationProvider";

const ProtectedRoute = ({ children, roles }) => {
  if (!authService.isAuthenticated()) {
    return <Navigate to="/login" replace />;
  }

  if (roles?.length && !roles.includes(authService.getCurrentRole())) {
    return <Navigate to={authService.getHomePath()} replace />;
  }

  return children;
};

const AppLayout = ({ children }) => {
  return (
    <div>
      <Sidebar />
      <div className="min-h-screen md:pl-64">
        <Navbar />
        <main>{children}</main>
      </div>
    </div>
  );
};

function App() {
  return (
    <NotificationProvider>
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/verify-email" element={<VerifyEmail />} />
        <Route path="/forgot-password" element={<ForgotPassword />} />
        <Route path="/reset-password" element={<ResetPassword />} />

        <Route
          path="/dashboard"
          element={
            <ProtectedRoute roles={["university"]}>
              <AppLayout>
                <Dashboard />
              </AppLayout>
            </ProtectedRoute>
          }
        />

        <Route
          path="/alumni"
          element={
            <ProtectedRoute roles={["university"]}>
              <AppLayout>
                <Alumni />
              </AppLayout>
            </ProtectedRoute>
          }
        />

        <Route
          path="/api-keys"
          element={
            <ProtectedRoute roles={["university"]}>
              <AppLayout>
                <ApiKeys />
              </AppLayout>
            </ProtectedRoute>
          }
        />

        <Route
          path="/my-profile"
          element={
            <ProtectedRoute roles={["alumni"]}>
              <AppLayout>
                <MyProfile />
              </AppLayout>
            </ProtectedRoute>
          }
        />

        <Route
          path="/bidding"
          element={
            <ProtectedRoute roles={["alumni"]}>
              <AppLayout>
                <Bidding />
              </AppLayout>
            </ProtectedRoute>
          }
        />

        <Route path="*" element={<Navigate to={authService.isAuthenticated() ? authService.getHomePath() : "/login"} replace />} />
      </Routes>
    </NotificationProvider>
  );
}

export default App;
