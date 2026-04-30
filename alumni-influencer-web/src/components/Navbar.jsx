import { useNavigate } from "react-router-dom";
import { authService } from "../services/authService";

const Navbar = () => {
  const navigate = useNavigate();
  const user = authService.getCurrentUser();
  const roleLabel = user?.role === "university" ? "University Analytics" : "Alumni Portal";

  const handleLogout = async () => {
    await authService.logout();
    navigate("/login");
  };

  return (
    <header className="flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4 shadow-sm">
      <div>
        <h2 className="text-lg font-semibold text-slate-800">Alumni Influencer Platform</h2>
        <p className="text-xs text-slate-500">{roleLabel}</p>
      </div>
    </header>
  );
};

export default Navbar;
