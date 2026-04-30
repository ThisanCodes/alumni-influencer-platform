import { useEffect, useMemo, useState } from "react";
import api from "../services/api";
import Loader from "../components/Loader";
import { useNotification } from "../components/NotificationProvider";
import { getApiErrorMessage } from "../services/apiError";

const emptyProfile = {
  first_name: "",
  last_name: "",
  bio: "",
  linkedin_url: "",
};

const sections = [
  {
    key: "degrees",
    title: "Degrees",
    endpoint: "/degrees",
    fields: [
      { name: "title", label: "Degree / Programme", required: true },
      { name: "university", label: "University", required: true },
      { name: "url", label: "Official URL", type: "url" },
      { name: "completion_date", label: "Completion Date", type: "date", required: true },
    ],
    display: (item) => `${item.title || "-"} at ${item.university || "-"} (${item.completion_date || "-"})`,
  },
  {
    key: "certifications",
    title: "Certifications",
    endpoint: "/certifications",
    fields: [
      { name: "name", label: "Certification", required: true },
      { name: "issuing_organization", label: "Issuing Organization" },
      { name: "url", label: "Course URL", type: "url" },
      { name: "completion_date", label: "Completion Date", type: "date", required: true },
    ],
    display: (item) => `${item.name || "-"} (${item.completion_date || "-"})`,
  },
  {
    key: "licences",
    title: "Licences",
    endpoint: "/licences",
    fields: [
      { name: "name", label: "Licence", required: true },
      { name: "url", label: "Awarding Body URL", type: "url" },
      { name: "completion_date", label: "Completion Date", type: "date", required: true },
    ],
    display: (item) => `${item.name || "-"} (${item.completion_date || "-"})`,
  },
  {
    key: "courses",
    title: "Short Professional Courses",
    endpoint: "/courses",
    fields: [
      { name: "name", label: "Course", required: true },
      { name: "provider", label: "Provider", required: true },
      { name: "url", label: "Course URL", type: "url" },
      { name: "completion_date", label: "Completion Date", type: "date", required: true },
    ],
    display: (item) => `${item.name || "-"} by ${item.provider || "-"} (${item.completion_date || "-"})`,
  },
  {
    key: "employment",
    title: "Employment History",
    endpoint: "/employment-history",
    fields: [
      { name: "company", label: "Company", required: true },
      { name: "role", label: "Role / Industry Sector", required: true },
      { name: "start_date", label: "Start Date", type: "date", required: true },
      { name: "end_date", label: "End Date", type: "date" },
      { name: "is_current", label: "Current Role", type: "checkbox" },
    ],
    display: (item) => `${item.role || "-"} at ${item.company || "-"} (${item.start_date || "-"} to ${item.is_current ? "Present" : item.end_date || "-"})`,
  },
];

const buildEmptyRecord = (fields) =>
  fields.reduce((record, field) => {
    record[field.name] = field.type === "checkbox" ? false : "";
    return record;
  }, {});

const unwrapData = (response) => response?.data?.data ?? response?.data ?? [];

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";
const assetBaseUrl = apiBaseUrl.replace(/\/api\/?$/, "");

const getProfileImageUrl = (path) => {
  if (!path) return "";
  if (/^https?:\/\//i.test(path)) return path;

  return `${assetBaseUrl}/${path.replace(/^\/+/, "")}`;
};

const MyProfile = () => {
  const notification = useNotification();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [profile, setProfile] = useState(null);
  const [profileForm, setProfileForm] = useState(emptyProfile);
  const [imageFile, setImageFile] = useState(null);
  const [records, setRecords] = useState({});
  const [drafts, setDrafts] = useState({});
  const profileImageUrl = getProfileImageUrl(profile?.profile_image);

  const initialDrafts = useMemo(
    () =>
      sections.reduce((next, section) => {
        next[section.key] = buildEmptyRecord(section.fields);
        return next;
      }, {}),
    []
  );

  const loadProfile = async () => {
    try {
      const response = await api.get("/profile");
      const data = unwrapData(response);
      setProfile(data);
      setProfileForm({
        first_name: data.first_name || "",
        last_name: data.last_name || "",
        bio: data.bio || "",
        linkedin_url: data.linkedin_url || "",
      });
    } catch (err) {
      if (err?.response?.status !== 404) {
        throw err;
      }
      setProfile(null);
      setProfileForm(emptyProfile);
    }
  };

  const loadSection = async (section) => {
    const response = await api.get(section.endpoint);
    return Array.isArray(unwrapData(response)) ? unwrapData(response) : [];
  };

  const loadAll = async () => {
    setLoading(true);
    try {
      await loadProfile();
      const entries = await Promise.all(sections.map(async (section) => [section.key, await loadSection(section)]));
      setRecords(Object.fromEntries(entries));
      setDrafts(initialDrafts);
    } catch (err) {
      const nextError = getApiErrorMessage(err, "Failed to load profile details.");
      notification.error(nextError);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadAll();
  }, []);

  const saveProfile = async (event) => {
    event.preventDefault();
    setSaving(true);

    try {
      const response = profile?.id ? await api.put(`/profile/${profile.id}`, profileForm) : await api.post("/profile", profileForm);
      const savedProfile = unwrapData(response);
      setProfile(savedProfile);

      if (imageFile) {
        const formData = new FormData();
        formData.append("profile_image", imageFile);
        await api.post("/profile/upload-image", formData, {
          headers: { "Content-Type": "multipart/form-data" },
        });
        setImageFile(null);
        await loadProfile();
      }

      const nextMessage = "Profile saved successfully.";
      notification.success(nextMessage);
    } catch (err) {
      const nextError = getApiErrorMessage(err, "Could not save profile.");
      notification.error(nextError);
    } finally {
      setSaving(false);
    }
  };

  const updateDraft = (sectionKey, field, value) => {
    setDrafts((prev) => ({
      ...prev,
      [sectionKey]: {
        ...prev[sectionKey],
        [field]: value,
      },
    }));
  };

  const resetDraft = (section) => {
    setDrafts((prev) => ({
      ...prev,
      [section.key]: buildEmptyRecord(section.fields),
    }));
  };

  const editRecord = (section, item) => {
    const nextDraft = buildEmptyRecord(section.fields);
    section.fields.forEach((field) => {
      nextDraft[field.name] = field.type === "checkbox" ? Boolean(item[field.name]) : item[field.name] || "";
    });
    nextDraft.id = item.id;
    setDrafts((prev) => ({ ...prev, [section.key]: nextDraft }));
  };

  const saveRecord = async (section, event) => {
    event.preventDefault();

    try {
      const draft = drafts[section.key];
      const payload = section.fields.reduce((next, field) => {
        next[field.name] = draft[field.name];
        return next;
      }, {});

      if (draft.id) {
        await api.put(`${section.endpoint}/${draft.id}`, payload);
      } else {
        await api.post(section.endpoint, payload);
      }

      const refreshedRecords = await loadSection(section);
      setRecords((prev) => ({ ...prev, [section.key]: refreshedRecords }));
      resetDraft(section);
      const nextMessage = `${section.title} saved successfully.`;
      notification.success(nextMessage);
    } catch (err) {
      const nextError = getApiErrorMessage(err, `Could not save ${section.title.toLowerCase()}.`);
      notification.error(nextError);
    }
  };

  const deleteRecord = async (section, item) => {
    if (!window.confirm(`Delete this ${section.title.toLowerCase()} record?`)) {
      return;
    }

    try {
      await api.delete(`${section.endpoint}/${item.id}`);
      setRecords((prev) => ({ ...prev, [section.key]: prev[section.key].filter((row) => row.id !== item.id) }));
      if (drafts[section.key]?.id === item.id) {
        resetDraft(section);
      }
      const nextMessage = `${section.title} deleted successfully.`;
      notification.success(nextMessage);
    } catch (err) {
      const nextError = getApiErrorMessage(err, `Could not delete ${section.title.toLowerCase()}.`);
      notification.error(nextError);
    }
  };

  if (loading) return <Loader text="Loading your profile..." />;

  return (
    <div className="space-y-5 p-5">
      <div>
        <h2 className="text-xl font-semibold text-slate-800">My Profile</h2>
        <p className="text-sm text-slate-500">Manage the alumni profile information used by the platform and analytics dashboard.</p>
      </div>

      <form onSubmit={saveProfile} className="space-y-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <h3 className="font-semibold text-slate-800">Personal Information</h3>
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          <label className="text-sm font-medium text-slate-700">
            First Name
            <input
              required
              value={profileForm.first_name}
              onChange={(e) => setProfileForm((prev) => ({ ...prev, first_name: e.target.value }))}
              className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"
            />
          </label>
          <label className="text-sm font-medium text-slate-700">
            Last Name
            <input
              required
              value={profileForm.last_name}
              onChange={(e) => setProfileForm((prev) => ({ ...prev, last_name: e.target.value }))}
              className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"
            />
          </label>
          <label className="text-sm font-medium text-slate-700 md:col-span-2">
            LinkedIn URL
            <input
              type="url"
              value={profileForm.linkedin_url}
              onChange={(e) => setProfileForm((prev) => ({ ...prev, linkedin_url: e.target.value }))}
              className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"
            />
          </label>
          <label className="text-sm font-medium text-slate-700 md:col-span-2">
            Biography
            <textarea
              rows={4}
              value={profileForm.bio}
              onChange={(e) => setProfileForm((prev) => ({ ...prev, bio: e.target.value }))}
              className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"
            />
          </label>
          <div className="text-sm font-medium text-slate-700 md:col-span-2">
            Profile Image
            <div className="mt-2 flex flex-wrap items-center gap-4 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4">
              {profileImageUrl ? (
                <img
                  src={profileImageUrl}
                  alt="Profile"
                  className="h-20 w-20 rounded-full border border-slate-200 object-cover"
                />
              ) : (
                <div className="flex h-20 w-20 items-center justify-center rounded-full bg-white text-xs text-slate-400 ring-1 ring-slate-200">
                  No image
                </div>
              )}
              <div className="min-w-0 flex-1">
                <label className="inline-flex cursor-pointer items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                  Choose Image
                  <input
                    type="file"
                    accept="image/png,image/jpeg"
                    onChange={(e) => setImageFile(e.target.files?.[0] || null)}
                    className="sr-only"
                  />
                </label>
                <p className="mt-2 truncate text-xs text-slate-500">
                  {imageFile ? imageFile.name : "PNG or JPEG image"}
                </p>
              </div>
            </div>
          </div>
        </div>
        <button disabled={saving} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">
          {saving ? "Saving..." : profile?.id ? "Update Profile" : "Create Profile"}
        </button>
      </form>

      {sections.map((section) => {
        const draft = drafts[section.key] || buildEmptyRecord(section.fields);
        const items = records[section.key] || [];

        return (
          <section key={section.key} className="space-y-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <h3 className="font-semibold text-slate-800">{section.title}</h3>
              {draft.id ? (
                <button type="button" onClick={() => resetDraft(section)} className="text-sm font-medium text-slate-600 hover:text-slate-900">
                  Cancel edit
                </button>
              ) : null}
            </div>

            <form onSubmit={(event) => saveRecord(section, event)} className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
              {section.fields.map((field) => (
                <label key={field.name} className="text-sm font-medium text-slate-700">
                  {field.label}
                  {field.type === "checkbox" ? (
                    <input
                      type="checkbox"
                      checked={Boolean(draft[field.name])}
                      onChange={(e) => updateDraft(section.key, field.name, e.target.checked)}
                      className="ml-3"
                    />
                  ) : (
                    <input
                      required={field.required}
                      type={field.type || "text"}
                      value={draft[field.name] || ""}
                      onChange={(e) => updateDraft(section.key, field.name, e.target.value)}
                      className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"
                    />
                  )}
                </label>
              ))}
              <div className="flex items-end">
                <button className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                  {draft.id ? "Update" : "Add"}
                </button>
              </div>
            </form>

            <div className="space-y-2">
              {items.length ? (
                items.map((item) => (
                  <div key={item.id} className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 p-3">
                    <p className="text-sm text-slate-700">{section.display(item)}</p>
                    <div className="flex gap-2">
                      <button onClick={() => editRecord(section, item)} className="rounded-md bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                        Edit
                      </button>
                      <button onClick={() => deleteRecord(section, item)} className="rounded-md bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 hover:bg-red-100">
                        Delete
                      </button>
                    </div>
                  </div>
                ))
              ) : (
                <p className="rounded-lg bg-slate-50 p-3 text-sm text-slate-500">No records added yet.</p>
              )}
            </div>
          </section>
        );
      })}
    </div>
  );
};

export default MyProfile;
