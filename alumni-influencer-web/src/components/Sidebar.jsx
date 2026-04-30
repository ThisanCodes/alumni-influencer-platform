import { NavLink, useNavigate } from "react-router-dom";
import { authService } from "../services/authService";

const iconClass = "h-5 w-5";
const icons = {
  dashboard: (
    <svg aria-hidden="true" className={iconClass} fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 13.5h6.75V3.75H3.75v9.75Zm9.75 6.75h6.75v-9.75H13.5v9.75Zm0-13.5h6.75v-3H13.5v3ZM3.75 20.25h6.75v-3H3.75v3Z" />
    </svg>
  ),
  alumni: (
    <svg aria-hidden="true" className={iconClass} fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0" />
    </svg>
  ),
  keys: (
    <svg aria-hidden="true" className={iconClass} fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 7.5a4.5 4.5 0 1 0-3.2 4.31L21 20.25V16.5h-3.75v-3.75h-3.75l-.95-.94" />
    </svg>
  ),
  profile: (
    <svg aria-hidden="true" className={iconClass} fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" d="M12 12a4.125 4.125 0 1 0 0-8.25A4.125 4.125 0 0 0 12 12ZM4.5 20.25a7.5 7.5 0 0 1 15 0" />
    </svg>
  ),
  bidding: (
    <svg aria-hidden="true" className={iconClass} fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v12m3-9.75h-4.5a2.25 2.25 0 0 0 0 4.5h3a2.25 2.25 0 0 1 0 4.5H9M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
  ),
  logout: (
    <svg aria-hidden="true" className={iconClass} fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 12h8.25m0 0-2.625-2.625M20.25 12l-2.625 2.625" />
    </svg>
  ),
};

const sections = [
  {
    title: "University Analytics",
    roles: ["university"],
    links: [
      { to: "/dashboard", label: "Dashboard", icon: icons.dashboard },
      { to: "/alumni", label: "Alumni", icon: icons.alumni },
    ],
  },
  {
    title: "Developer",
    roles: ["university"],
    links: [
      { to: "/api-keys", label: "API Keys", icon: icons.keys },
    ],
  },
  {
    title: "Alumni Portal",
    roles: ["alumni"],
    links: [
      { to: "/my-profile", label: "My Profile", icon: icons.profile },
      { to: "/bidding", label: "Bidding", icon: icons.bidding },
    ],
  },
];

const Sidebar = () => {
  const navigate = useNavigate();
  const role = authService.getCurrentRole();
  const user = authService.getCurrentUser();
  const visibleSections = sections.filter((section) => section.roles.includes(role));

  const handleLogout = async () => {
    await authService.logout();
    navigate("/login");
  };

  return (
    <aside className="z-30 flex w-full flex-col border-b border-slate-200 bg-white p-4 text-slate-800 shadow-sm md:fixed md:inset-y-0 md:left-0 md:w-64 md:border-b-0 md:border-r">
      <div className="mb-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
        <div className="mb-3 h-1.5 w-12 rounded-full bg-indigo-600" />
        <h1 className="text-lg font-bold text-slate-800">Alumni Influencer</h1>
        <p className="mt-1 truncate text-xs text-slate-500">{user?.email || "Logged User"}</p>
      </div>

      <nav className="flex flex-1 gap-4 overflow-x-auto md:flex-col md:gap-6 md:overflow-visible">
        {visibleSections.map((section) => (
          <div key={section.title} className="min-w-max md:min-w-0">
            <p className="mb-2 px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">{section.title}</p>
            <div className="flex gap-2 md:flex-col">
              {section.links.map((link) => (
                <NavLink
                  key={link.to}
                  to={link.to}
                  className={({ isActive }) =>
                    `flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition ${
                      isActive ? "bg-indigo-600 text-white shadow-sm" : "text-slate-600 hover:bg-slate-100 hover:text-indigo-700"
                    }`
                  }
                >
                  {link.icon}
                  {link.label}
                </NavLink>
              ))}
            </div>
          </div>
        ))}
      </nav>

      <button
        type="button"
        onClick={handleLogout}
        className="mt-6 flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-indigo-600 hover:bg-indigo-600 hover:text-white"
      >
        {icons.logout}
        Logout
      </button>
    </aside>
  );
};

export default Sidebar;
