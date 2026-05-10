import { useEffect, useState } from "react";
import api from "../services/api";
import Loader from "../components/Loader";
import { useNotification } from "../components/NotificationProvider";
import { getApiErrorMessage } from "../services/apiError";

const ApiKeys = () => {
  const notification = useNotification();
  const [loading, setLoading] = useState(true);
  const [creating, setCreating] = useState(false);
  const [statsLoading, setStatsLoading] = useState(false);
  const [keys, setKeys] = useState([]);
  const [generatedKey, setGeneratedKey] = useState(null);
  const [selectedStats, setSelectedStats] = useState(null);
  const [form, setForm] = useState({
    name: "",
    abilities: "read:alumni",
    expires_at: "+7 days",
  });

  const formatAbilities = (abilities) => {
    if (Array.isArray(abilities)) return abilities.join(", ");

    try {
      const parsed = JSON.parse(abilities || "[]");
      return Array.isArray(parsed) ? parsed.join(", ") : "No scopes";
    } catch {
      return abilities || "No scopes";
    }
  };

  const loadKeys = async () => {
    setLoading(true);
    try {
      const { data } = await api.get("/api-keys");
      setKeys(Array.isArray(data?.data) ? data.data : []);
    } catch (err) {
      const nextError = getApiErrorMessage(err, "Failed to fetch API keys.");
      notification.error(nextError);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadKeys();
  }, []);

  const handleChange = (e) => {
    setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
  };

  const generateKey = async (e) => {
    e.preventDefault();
    setCreating(true);
    setGeneratedKey(null);

    try {
      const abilities = form.abilities
        .split(",")
        .map((ability) => ability.trim())
        .filter(Boolean);

      const { data } = await api.post("/api-keys", {
        name: form.name,
        abilities: abilities.length ? abilities : ["*"],
        expires_at: form.expires_at || null,
      });

      setGeneratedKey(data?.data || null);
      const nextSuccess = "API key generated successfully. Copy it now because it will not be shown again.";
      notification.success(nextSuccess);
      setForm({ name: "", abilities: "read:alumni", expires_at: "+7 days" });
      await loadKeys();
    } catch (err) {
      const nextError = getApiErrorMessage(err, "Failed to generate API key.");
      notification.error(nextError);
    } finally {
      setCreating(false);
    }
  };

  const viewStats = async (id) => {
    setStatsLoading(true);
    try {
      const { data } = await api.get(`/api-keys/${id}/stats`);
      setSelectedStats(data?.data || null);
    } catch (err) {
      const nextError = getApiErrorMessage(err, "Failed to fetch key statistics.");
      notification.error(nextError);
    } finally {
      setStatsLoading(false);
    }
  };

  const revokeKey = async (id) => {
    try {
      await api.delete(`/api-keys/${id}`);
      setKeys((prev) =>
        prev.map((item) => (String(item.id) === String(id) ? { ...item, is_revoked: true } : item))
      );
      const nextSuccess = "API key revoked successfully.";
      notification.success(nextSuccess);
    } catch (err) {
      const nextError = getApiErrorMessage(err, "Failed to revoke API key.");
      notification.error(nextError);
    }
  };

  if (loading) return <Loader text="Loading API keys..." />;

  return (
    <div className="space-y-4 p-5">
      <div>
        <h2 className="text-xl font-semibold text-slate-800">Manage API Keys</h2>
        <p className="text-sm text-slate-500">Generate, view, inspect statistics, and revoke client API keys.</p>
      </div>

      <form onSubmit={generateKey} className="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200">
        <h3 className="mb-3 font-semibold text-slate-800">Generate API Key</h3>
        <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
          <input
            required
            name="name"
            value={form.name}
            onChange={handleChange}
            placeholder="Key name"
            className="rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500"
          />
          <input
            required
            name="abilities"
            value={form.abilities}
            onChange={handleChange}
            placeholder="read:alumni, read:analytics"
            className="rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500"
          />
          <input
            name="expires_at"
            value={form.expires_at}
            onChange={handleChange}
            placeholder="+7 days"
            className="rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500"
          />
        </div>
        <button
          disabled={creating}
          className="mt-3 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-70"
        >
          {creating ? "Generating..." : "Generate API Key"}
        </button>
      </form>

      {generatedKey ? (
        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4">
          <p className="text-sm font-semibold text-amber-900">New API Key</p>
          <p className="mt-1 break-all rounded bg-white p-3 font-mono text-xs text-slate-800">{generatedKey.key}</p>
          <p className="mt-2 text-xs text-amber-800">Copy this now. The full key will not be shown again.</p>
        </div>
      ) : null}

      <div className="space-y-3">
        <h3 className="font-semibold text-slate-800">View API Keys List</h3>
        {keys.length ? (
          keys.map((item) => (
            <div
              key={item.id}
              className="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200"
            >
              <div>
                <p className="font-medium text-slate-800">
                  {item.name || "Unnamed key"}{" "}
                  {item.is_revoked === true || item.is_revoked === "t" ? (
                    <span className="rounded-full bg-red-50 px-2 py-0.5 text-xs text-red-700">Revoked</span>
                  ) : null}
                </p>
                <p className="text-xs text-slate-500">User ID: {item.user_id || "-"}</p>
                <p className="text-xs text-slate-500">Prefix: {item.key_prefix || "-"}</p>
                <p className="text-xs text-slate-500">Scopes: {formatAbilities(item.abilities)}</p>
                <p className="text-xs text-slate-500">Expires: {item.expires_at || "Never"}</p>
                <p className="text-xs text-slate-500">Last used: {item.last_used_at || "Never"}</p>
              </div>
              <div className="flex gap-2">
                <button
                  onClick={() => viewStats(item.id)}
                  className="rounded-md bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                >
                  View Statistics
                </button>
                <button
                  disabled={item.is_revoked === true || item.is_revoked === "t"}
                  onClick={() => revokeKey(item.id)}
                  className="rounded-md bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  Revoke
                </button>
              </div>
            </div>
          ))
        ) : (
          <div className="rounded-lg bg-white p-6 text-center text-slate-500 shadow-sm ring-1 ring-slate-200">
            No API keys available.
          </div>
        )}
      </div>

      {statsLoading ? <Loader text="Loading key statistics..." /> : null}
      {selectedStats ? (
        <div className="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200">
          <div className="mb-3 flex items-center justify-between gap-3">
            <div>
              <h3 className="font-semibold text-slate-800">View Key Statistics</h3>
              <p className="text-xs text-slate-500">
                {selectedStats.name} ({selectedStats.key_prefix})
              </p>
            </div>
            <button
              onClick={() => setSelectedStats(null)}
              className="rounded-md bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200"
            >
              Close
            </button>
          </div>
          <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div className="rounded-md bg-slate-50 p-3">
              <p className="text-xs text-slate-500">Total Requests</p>
              <p className="text-2xl font-semibold text-slate-800">{selectedStats.statistics?.total_requests || 0}</p>
            </div>
            <div className="rounded-md bg-slate-50 p-3">
              <p className="text-xs text-slate-500">Scopes</p>
              <p className="text-sm font-semibold text-slate-800">{formatAbilities(selectedStats.abilities)}</p>
            </div>
            <div className="rounded-md bg-slate-50 p-3">
              <p className="text-xs text-slate-500">Last Used</p>
              <p className="text-sm font-semibold text-slate-800">{selectedStats.last_used_at || "Never"}</p>
            </div>
          </div>

          <div className="mt-4">
            <h4 className="mb-2 text-sm font-semibold text-slate-700">Endpoint Breakdown</h4>
            {(selectedStats.statistics?.endpoint_breakdown || []).length ? (
              <div className="overflow-x-auto">
                <table className="min-w-full text-xs">
                  <thead className="bg-slate-50 text-left text-slate-600">
                    <tr>
                      <th className="px-3 py-2">Method</th>
                      <th className="px-3 py-2">Endpoint</th>
                      <th className="px-3 py-2">Hits</th>
                    </tr>
                  </thead>
                  <tbody>
                    {selectedStats.statistics.endpoint_breakdown.map((row, idx) => (
                      <tr key={`${row.method}-${row.endpoint}-${idx}`} className="border-t border-slate-100">
                        <td className="px-3 py-2">{row.method}</td>
                        <td className="px-3 py-2">{row.endpoint}</td>
                        <td className="px-3 py-2">{row.hit_count}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <p className="text-xs text-slate-500">No usage statistics yet.</p>
            )}
          </div>

          <div className="mt-4">
            <h4 className="mb-2 text-sm font-semibold text-slate-700">Recent Key Usage</h4>
            {(selectedStats.statistics?.recent_requests || []).length ? (
              <div className="overflow-x-auto">
                <table className="min-w-full text-xs">
                  <thead className="bg-slate-50 text-left text-slate-600">
                    <tr>
                      <th className="px-3 py-2">Time</th>
                      <th className="px-3 py-2">Method</th>
                      <th className="px-3 py-2">Endpoint</th>
                      <th className="px-3 py-2">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {selectedStats.statistics.recent_requests.map((row) => (
                      <tr key={row.id} className="border-t border-slate-100">
                        <td className="px-3 py-2">{row.created_at || "-"}</td>
                        <td className="px-3 py-2">{row.method}</td>
                        <td className="px-3 py-2">{row.endpoint}</td>
                        <td className="px-3 py-2">{row.response_code || "-"}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <p className="text-xs text-slate-500">No recent key usage yet.</p>
            )}
          </div>
        </div>
      ) : null}
    </div>
  );
};

export default ApiKeys;
