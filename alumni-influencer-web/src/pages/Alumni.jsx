import { useEffect, useState } from "react";
import clientApi from "../services/clientApi";
import Loader from "../components/Loader";
import { useNotification } from "../components/NotificationProvider";
import { getApiErrorMessage } from "../services/apiError";

const Alumni = () => {
  const notification = useNotification();
  const [loading, setLoading] = useState(true);
  const [alumni, setAlumni] = useState([]);
  const [filterOptions, setFilterOptions] = useState({
    programmes: [],
    industries: [],
    graduationYears: [],
  });
  const [selectedProfile, setSelectedProfile] = useState(null);
  const [profileLoading, setProfileLoading] = useState(false);
  const [draftFilters, setDraftFilters] = useState({
    search: "",
    programme: "",
    industry: "",
    graduationDate: "",
  });
  const [filters, setFilters] = useState({
    search: "",
    programme: "",
    industry: "",
    graduationDate: "",
  });

  useEffect(() => {
    const loadOptions = async () => {
      try {
        const { data } = await clientApi.get("/profile/filter-options");
        const payload = data?.data || {};
        setFilterOptions({
          programmes: Array.isArray(payload.programmes) ? payload.programmes : [],
          industries: Array.isArray(payload.industries) ? payload.industries : [],
          graduationYears: Array.isArray(payload.graduation_years) ? payload.graduation_years : [],
        });
      } catch (err) {
        setFilterOptions({ programmes: [], industries: [], graduationYears: [] });
        notification.error(getApiErrorMessage(err, "Failed to load filter options."));
      }
    };

    loadOptions();
  }, []);

  useEffect(() => {
    const load = async () => {
      setLoading(true);
      try {
        const params = {};
        if (filters.search) params.search = filters.search;
        if (filters.programme) params.programme = filters.programme;
        if (filters.industry) params.industry = filters.industry;
        if (filters.graduationDate) params.graduation_year = filters.graduationDate;

        const { data } = await clientApi.get("/profile/all", { params });
        const source = Array.isArray(data)
          ? data
          : Array.isArray(data?.data)
          ? data.data
          : data?.data
          ? [data.data]
          : [];

        const normalized = source.map((item) => ({
          id: item.id,
          userId: item.user_id,
          fullName: `${item.first_name || ""} ${item.last_name || ""}`.trim(),
          email: item.email || item.user_email || "-",
          latestDegree: item.latest_degree || "-",
          currentIndustry: item.current_industry || "-",
          latestDegreeGraduationDate: item.latest_degree_graduation_date || "",
        }));

        setAlumni(normalized);
      } catch (err) {
        const nextError = getApiErrorMessage(err, "Failed to load alumni.");
        notification.error(nextError);
      } finally {
        setLoading(false);
      }
    };
    load();
  }, [filters]);

  const handleSearch = () => setFilters(draftFilters);
  const handleReset = () => {
    const empty = { search: "", programme: "", industry: "", graduationDate: "" };
    setDraftFilters(empty);
    setFilters(empty);
  };

  const handleViewFullProfile = async (userId) => {
    if (!userId) return;
    setProfileLoading(true);
    try {
      const { data } = await clientApi.get("/profile/full", { params: { user_id: userId } });
      setSelectedProfile(data?.data || null);
    } catch (err) {
      setSelectedProfile(null);
      const nextError = getApiErrorMessage(err, "Failed to load full profile.");
      notification.error(nextError);
    } finally {
      setProfileLoading(false);
    }
  };

  if (loading) return <Loader text="Loading alumni profiles..." />;

  return (
    <div className="space-y-3 p-4">
      <h2 className="text-lg font-semibold text-slate-800">Alumni</h2>
      <div className="grid grid-cols-1 gap-2 md:grid-cols-4">
        <input
          placeholder="Search name/email"
          className="rounded-md border border-slate-300 px-2.5 py-1.5 text-sm"
          value={draftFilters.search}
          onChange={(e) => setDraftFilters((prev) => ({ ...prev, search: e.target.value }))}
        />
        <select
          className="rounded-md border border-slate-300 px-2.5 py-1.5 text-sm"
          value={draftFilters.programme}
          onChange={(e) => setDraftFilters((prev) => ({ ...prev, programme: e.target.value }))}
        >
          <option value="">All programmes</option>
          {filterOptions.programmes.map((option) => (
            <option key={option} value={option}>
              {option}
            </option>
          ))}
        </select>
        <select
          className="rounded-md border border-slate-300 px-2.5 py-1.5 text-sm"
          value={draftFilters.industry}
          onChange={(e) => setDraftFilters((prev) => ({ ...prev, industry: e.target.value }))}
        >
          <option value="">All industry sectors</option>
          {filterOptions.industries.map((option) => (
            <option key={option} value={option}>
              {option}
            </option>
          ))}
        </select>
        <select
          className="rounded-md border border-slate-300 px-2.5 py-1.5 text-sm"
          value={draftFilters.graduationDate}
          onChange={(e) => setDraftFilters((prev) => ({ ...prev, graduationDate: e.target.value }))}
        >
          <option value="">All graduation years</option>
          {filterOptions.graduationYears.map((year) => (
            <option key={year} value={year}>
              {year}
            </option>
          ))}
        </select>
      </div>
      <div className="flex gap-2">
        <button onClick={handleSearch} className="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">
          Search
        </button>
        <button onClick={handleReset} className="rounded-md bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-300">
          Reset
        </button>
      </div>

      <div className="overflow-x-auto rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
        <table className="min-w-full text-xs">
          <thead className="bg-slate-50 text-left text-slate-600">
            <tr>
              <th className="px-3 py-2">Name</th>
              <th className="px-3 py-2">Email</th>
              <th className="px-3 py-2">Latest Degree</th>
              <th className="px-3 py-2">Current Industry</th>
              <th className="px-3 py-2">Graduation Date</th>
              <th className="px-3 py-2">View</th>
            </tr>
          </thead>
          <tbody>
            {alumni.length ? (
              alumni.map((item, idx) => (
                <tr key={item.id || idx} className="border-t border-slate-100">
                  <td className="px-3 py-2">{item.fullName || "-"}</td>
                  <td className="px-3 py-2">{item.email || "-"}</td>
                  <td className="px-3 py-2">{item.latestDegree || "-"}</td>
                  <td className="px-3 py-2">{item.currentIndustry || "-"}</td>
                  <td className="px-3 py-2">{item.latestDegreeGraduationDate || "-"}</td>
                  <td className="px-3 py-2">
                    <button
                      onClick={() => handleViewFullProfile(item.userId)}
                      className="inline-flex items-center justify-center rounded-md bg-indigo-50 p-1.5 text-indigo-700 hover:bg-indigo-100"
                      title="View Full Profile"
                    >
                      <svg
                        className="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="1.8"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"
                        />
                        <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                      </svg>
                    </button>
                  </td>
                </tr>
              ))
            ) : (
              <tr>
                <td colSpan={6} className="px-4 py-10 text-center text-slate-500">
                  No alumni found for current filters.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      {profileLoading ? <Loader text="Loading full profile..." /> : null}
      {selectedProfile ? (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
          onClick={() => setSelectedProfile(null)}
        >
          <div
            className="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-xl bg-white p-5 shadow-lg ring-1 ring-slate-200"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="mb-3 flex items-center justify-between">
              <h3 className="text-lg font-semibold text-slate-800">
                {selectedProfile.first_name} {selectedProfile.last_name}
              </h3>
              <button
                onClick={() => setSelectedProfile(null)}
                className="rounded-md bg-slate-100 px-3 py-1 text-sm text-slate-700 hover:bg-slate-200"
              >
                Close
              </button>
            </div>
            <p className="text-sm text-slate-600">{selectedProfile.email || "-"}</p>
            <p className="mt-2 text-sm text-slate-700">{selectedProfile.bio || "-"}</p>
            <p className="mt-1 text-sm text-slate-700">
              LinkedIn:{" "}
              {selectedProfile.linkedin_url ? (
                <a
                  href={selectedProfile.linkedin_url}
                  target="_blank"
                  rel="noreferrer"
                  className="font-medium text-indigo-600 hover:underline"
                >
                  {selectedProfile.linkedin_url}
                </a>
              ) : (
                "-"
              )}
            </p>

            <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
              <div>
                <h4 className="mb-1 font-medium text-slate-800">Degrees</h4>
                <ul className="text-sm text-slate-600">
                  {(selectedProfile.degrees || []).length
                    ? selectedProfile.degrees.map((d) => <li key={d.id}>{d.title} ({d.completion_date})</li>)
                    : <li>-</li>}
                </ul>
              </div>
              <div>
                <h4 className="mb-1 font-medium text-slate-800">Certifications</h4>
                <ul className="text-sm text-slate-600">
                  {(selectedProfile.certifications || []).length
                    ? selectedProfile.certifications.map((c) => <li key={c.id}>{c.name} ({c.completion_date})</li>)
                    : <li>-</li>}
                </ul>
              </div>
              <div>
                <h4 className="mb-1 font-medium text-slate-800">Licences</h4>
                <ul className="text-sm text-slate-600">
                  {(selectedProfile.licences || []).length
                    ? selectedProfile.licences.map((l) => <li key={l.id}>{l.name} ({l.completion_date})</li>)
                    : <li>-</li>}
                </ul>
              </div>
              <div>
                <h4 className="mb-1 font-medium text-slate-800">Courses</h4>
                <ul className="text-sm text-slate-600">
                  {(selectedProfile.courses || []).length
                    ? selectedProfile.courses.map((c) => <li key={c.id}>{c.name} ({c.completion_date})</li>)
                    : <li>-</li>}
                </ul>
              </div>
              <div>
                <h4 className="mb-1 font-medium text-slate-800">Employment History</h4>
                <ul className="text-sm text-slate-600">
                  {(selectedProfile.employment_history || []).length
                    ? selectedProfile.employment_history.map((e) => <li key={e.id}>{e.role} @ {e.company}</li>)
                    : <li>-</li>}
                </ul>
              </div>
              <div>
                <h4 className="mb-1 font-medium text-slate-800">Event Participations</h4>
                <ul className="text-sm text-slate-600">
                  {(selectedProfile.event_participations || []).length
                    ? selectedProfile.event_participations.map((e) => <li key={e.id}>{e.event_name} ({e.event_date})</li>)
                    : <li>-</li>}
                </ul>
              </div>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  );
};

export default Alumni;
