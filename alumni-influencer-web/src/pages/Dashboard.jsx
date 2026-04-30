import { useEffect, useMemo, useRef, useState } from "react";
import { Bar, Doughnut, Line, Pie } from "react-chartjs-2";
import {
  ArcElement,
  BarElement,
  CategoryScale,
  Chart as ChartJS,
  Legend,
  LineElement,
  LinearScale,
  PointElement,
  Title,
  Tooltip,
} from "chart.js";
import KPIBox from "../components/KPIBox";
import ChartCard from "../components/ChartCard";
import Loader from "../components/Loader";
import { analyticsService } from "../services/analyticsService";
import { useNotification } from "../components/NotificationProvider";
import { getApiErrorMessage } from "../services/apiError";

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  ArcElement,
  Title,
  Tooltip,
  Legend
);

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: "bottom", labels: { color: "#475569", boxWidth: 14, padding: 16 } },
    tooltip: { enabled: true },
  },
  scales: {
    x: { grid: { color: "#e2e8f0" }, ticks: { color: "#64748b" } },
    y: { grid: { color: "#e2e8f0" }, ticks: { color: "#64748b" } },
  },
};

const circularChartOptions = {
  ...chartOptions,
  scales: undefined,
};

const randomColorForLabel = (label, alpha = 1) => {
  const text = String(label || "item");
  let hash = 0;
  for (let index = 0; index < text.length; index += 1) {
    hash = text.charCodeAt(index) + ((hash << 5) - hash);
  }
  const hue = Math.abs(hash) % 360;
  return `hsla(${hue}, 72%, 52%, ${alpha})`;
};

const buildColors = (labels, alpha = 1) => labels.map((label) => randomColorForLabel(label, alpha));

const formatBidAmount = (amount) => {
  if (amount === null || amount === undefined || amount === "") return "No bid recorded";
  const numericAmount = Number(amount);
  return Number.isNaN(numericAmount) ? amount : numericAmount.toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const escapeHtml = (value) =>
  String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");

const buildReportRows = (analyticsData) => {
  if (!analyticsData) return [];

  const featuredName = analyticsData.featuredAlumnus
    ? `${analyticsData.featuredAlumnus.first_name || ""} ${analyticsData.featuredAlumnus.last_name || ""}`.trim()
    : "";

  return [
    ["kpi", "total_alumni", analyticsData.kpi?.total_alumni ?? ""],
    ["kpi", "active_bidders", analyticsData.kpi?.active_bidders ?? ""],
    ["kpi", "bids_this_month", analyticsData.kpi?.bids_this_month ?? ""],
    ["kpi", "featured_profile", featuredName],
    ["kpi", "featured_bid_amount", analyticsData.featuredAlumnus?.winning_bid_amount ?? ""],
    ...(analyticsData.jobTitles || []).map((item) => ["job-titles", item.job_title, item.count]),
    ...(analyticsData.alumniByProgramme || []).map((item) => ["alumni-by-programme", item.programme, item.count]),
    ...(analyticsData.certificationsOverTime || []).map((item) => ["certifications-over-time", item.year, item.count]),
    ...(analyticsData.curriculumSkillsGapByProgramme || []).flatMap((item) => [
      ["curriculum-skills-gap-by-programme", `${item.programme} avg_certifications`, item.avg_certifications],
      ["curriculum-skills-gap-by-programme", `${item.programme} avg_licences`, item.avg_licences],
      ["curriculum-skills-gap-by-programme", `${item.programme} avg_courses`, item.avg_courses],
    ]),
    ...(analyticsData.topEmployers || []).map((item) => ["top-employers", item.sector, item.count]),
    ...(analyticsData.graduationTrends || []).map((item) => ["graduation-trends", item.year, item.count]),
  ];
};

const profileSections = [
  { key: "degrees", title: "Degrees", render: (item) => `${item.title || "-"} at ${item.university || "-"} (${item.completion_date || "-"})` },
  { key: "certifications", title: "Certifications", render: (item) => `${item.name || "-"} (${item.completion_date || "-"})` },
  { key: "licences", title: "Licences", render: (item) => `${item.name || "-"} (${item.completion_date || "-"})` },
  { key: "courses", title: "Courses", render: (item) => `${item.name || "-"} by ${item.provider || "-"} (${item.completion_date || "-"})` },
  { key: "employment_history", title: "Employment History", render: (item) => `${item.role || "-"} at ${item.company || "-"} (${item.start_date || "-"} to ${item.is_current ? "Present" : item.end_date || "-"})` },
];

const iconClass = "h-5 w-5";
const icons = {
  alumni: (
    <svg aria-hidden="true" className={iconClass} fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0" />
    </svg>
  ),
  bidders: (
    <svg aria-hidden="true" className={iconClass} fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" d="M7.5 8.25h9m-9 3.75h9m-9 3.75h5.25M6 21h12a2.25 2.25 0 0 0 2.25-2.25V5.25A2.25 2.25 0 0 0 18 3H6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 6 21Z" />
    </svg>
  ),
  bids: (
    <svg aria-hidden="true" className={iconClass} fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v12m3-9.75h-4.5a2.25 2.25 0 0 0 0 4.5h3a2.25 2.25 0 0 1 0 4.5H9M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
  ),
  featured: (
    <svg aria-hidden="true" className={iconClass} fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" d="m12 3 2.7 5.47 6.04.88-4.37 4.26 1.03 6.01L12 16.78l-5.4 2.84 1.03-6.01-4.37-4.26 6.04-.88L12 3Z" />
    </svg>
  ),
};

const Dashboard = () => {
  const notification = useNotification();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [showFeaturedModal, setShowFeaturedModal] = useState(false);
  const [data, setData] = useState({
    kpi: null,
    alumniByProgramme: [],
    certificationsOverTime: [],
    curriculumSkillsGapByProgramme: [],
    jobTitles: [],
    topEmployers: [],
    graduationTrends: [],
    featuredAlumnus: null,
  });
  const [filterOptions, setFilterOptions] = useState({
    programmes: [],
    graduationYears: [],
  });
  const [filters, setFilters] = useState({
    programme: "",
    graduationYear: "",
  });
  const [draftFilters, setDraftFilters] = useState(filters);

  const jobTitlesRef = useRef(null);
  const programmeRef = useRef(null);
  const certificationsRef = useRef(null);
  const curriculumSkillsRef = useRef(null);
  const employersRef = useRef(null);
  const graduationRef = useRef(null);

  useEffect(() => {
    const loadFilterOptions = async () => {
      try {
        const response = await analyticsService.getFilterOptions();
        const payload = response?.data || {};
        setFilterOptions({
          programmes: Array.isArray(payload.programmes) ? payload.programmes : [],
          graduationYears: Array.isArray(payload.graduation_years) ? payload.graduation_years : [],
        });
      } catch (err) {
        setFilterOptions({ programmes: [], graduationYears: [] });
        notification.error(getApiErrorMessage(err, "Failed to load dashboard filters."));
      }
    };

    loadFilterOptions();
  }, []);

  useEffect(() => {
    const load = async () => {
      setLoading(true);
      setError("");
      try {
        const params = {};
        if (filters.programme) params.programme = filters.programme;
        if (filters.graduationYear) params.graduation_year = filters.graduationYear;

        const [
          kpi,
          alumniByProgramme,
          certificationsOverTime,
          curriculumSkillsGapByProgramme,
          jobTitles,
          topEmployers,
          graduationTrends,
          featuredAlumnus,
        ] = await Promise.all([
          analyticsService.getKpi(params),
          analyticsService.getAlumniByProgramme(params),
          analyticsService.getCertificationsOverTime(params),
          analyticsService.getCurriculumSkillsGapByProgramme(params),
          analyticsService.getJobTitles(params),
          analyticsService.getTopEmployers(params),
          analyticsService.getGraduationTrends(params),
          analyticsService.getFeaturedAlumnus().catch(() => null),
        ]);

        const nextData = {
          kpi,
          alumniByProgramme,
          certificationsOverTime,
          curriculumSkillsGapByProgramme,
          jobTitles,
          topEmployers,
          graduationTrends,
          featuredAlumnus: featuredAlumnus?.data || null,
        };
        setData(nextData);
      } catch (err) {
        const nextError = getApiErrorMessage(err, "Failed to load analytics data.");
        setError(nextError);
        notification.error(nextError);
      } finally {
        setLoading(false);
      }
    };
    load();
  }, [filters]);

  const charts = useMemo(
    () => ({
      jobTitles: {
        labels: data.jobTitles.map((item) => item.job_title),
        datasets: [{
          label: "Most Common Job Titles",
          data: data.jobTitles.map((item) => item.count),
          backgroundColor: buildColors(data.jobTitles.map((item) => item.job_title), 0.85),
          borderColor: "#ffffff",
          borderWidth: 2,
        }],
      },
      programme: {
        labels: ["Programme Count"],
        datasets: data.alumniByProgramme.map((item) => ({
          label: item.programme,
          data: [item.count],
          backgroundColor: randomColorForLabel(item.programme, 0.8),
          borderRadius: 8,
        })),
      },
      certifications: {
        labels: data.certificationsOverTime.map((item) => item.year),
        datasets: [{
          label: "Certifications Over Time",
          data: data.certificationsOverTime.map((item) => item.count),
          borderColor: randomColorForLabel("certifications-over-time", 1),
          backgroundColor: randomColorForLabel("certifications-over-time", 0.14),
          borderWidth: 3,
          fill: true,
          tension: 0.35,
        }],
      },
      curriculumSkills: {
        labels: data.curriculumSkillsGapByProgramme.map((item) => item.programme),
        datasets: [
          {
            label: "Avg Certifications",
            data: data.curriculumSkillsGapByProgramme.map((item) => item.avg_certifications),
            backgroundColor: "hsla(24, 94%, 60%, 0.85)",
            borderRadius: 8,
          },
          {
            label: "Avg Licences",
            data: data.curriculumSkillsGapByProgramme.map((item) => item.avg_licences),
            backgroundColor: "hsla(258, 78%, 74%, 0.85)",
            borderRadius: 8,
          },
          {
            label: "Avg Courses",
            data: data.curriculumSkillsGapByProgramme.map((item) => item.avg_courses),
            backgroundColor: "hsla(187, 67%, 58%, 0.85)",
            borderRadius: 8,
          },
        ],
      },
      employers: {
        labels: data.topEmployers.map((item) => item.sector),
        datasets: [{
          label: "Top Employers",
          data: data.topEmployers.map((item) => item.count),
          backgroundColor: buildColors(data.topEmployers.map((item) => item.sector), 0.85),
          borderColor: "#ffffff",
          borderWidth: 2,
        }],
      },
      graduation: {
        labels: data.graduationTrends.map((item) => item.year),
        datasets: [{
          label: "Graduation Trends",
          data: data.graduationTrends.map((item) => item.count),
          borderColor: randomColorForLabel("graduation-trends", 1),
          backgroundColor: randomColorForLabel("graduation-trends", 0.14),
          borderWidth: 3,
          fill: true,
          tension: 0.35,
        }],
      },
    }),
    [data]
  );
  const reportRows = useMemo(() => buildReportRows(data), [data]);

  const downloadChartImage = (ref, fileName) => {
    const chart = ref?.current;
    if (!chart) return;
    const link = document.createElement("a");
    link.href = chart.toBase64Image();
    link.download = fileName;
    link.click();
  };

  const downloadCsv = () => {
    if (!reportRows.length) return;

    const csv = [
      "category,metric,value",
      ...reportRows.map((row) => row.map((cell) => `"${String(cell).replaceAll('"', '""')}"`).join(",")),
    ].join("\n");
    const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = "analytics-report.csv";
    link.click();
    URL.revokeObjectURL(url);
  };

  const downloadPdf = () => {
    if (!reportRows.length) return;

    const rows = reportRows
      .map(
        ([category, metric, value]) =>
          `<tr><td>${escapeHtml(category)}</td><td>${escapeHtml(metric)}</td><td>${escapeHtml(value)}</td></tr>`
      )
      .join("");
    const reportWindow = window.open("", "_blank");
    if (!reportWindow) return;

    reportWindow.document.write(`
      <!doctype html>
      <html>
        <head>
          <title>Analytics Report</title>
          <style>
            body { font-family: Arial, sans-serif; padding: 24px; color: #0f172a; }
            h1 { font-size: 22px; margin-bottom: 4px; }
            p { color: #475569; margin-top: 0; }
            table { border-collapse: collapse; width: 100%; margin-top: 20px; }
            th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; font-size: 12px; }
            th { background: #f1f5f9; }
          </style>
        </head>
        <body>
          <h1>Analytics Report</h1>
          <p>Generated ${escapeHtml(new Date().toLocaleString())}</p>
          <table>
            <thead>
              <tr><th>Category</th><th>Metric</th><th>Value</th></tr>
            </thead>
            <tbody>${rows}</tbody>
          </table>
          <script>
            window.onload = () => {
              window.print();
            };
          </script>
        </body>
      </html>
    `);
    reportWindow.document.close();
  };

  const featuredProfileName = data.featuredAlumnus
    ? `${data.featuredAlumnus.first_name || ""} ${data.featuredAlumnus.last_name || ""}`.trim()
    : "None";
  const featuredBidAmount = formatBidAmount(data.featuredAlumnus?.winning_bid_amount);
  const handleFilterChange = (event) => {
    setDraftFilters((prev) => ({ ...prev, [event.target.name]: event.target.value }));
  };
  const applyFilters = (event) => {
    event.preventDefault();
    setFilters(draftFilters);
  };
  const resetFilters = () => {
    const emptyFilters = { programme: "", graduationYear: "" };
    setDraftFilters(emptyFilters);
    setFilters(emptyFilters);
  };

  if (loading) return <Loader text="Loading dashboard analytics..." />;
  if (error) return <p className="rounded-md bg-red-50 p-4 text-red-700">{error}</p>;

  return (
    <div className="space-y-6 p-5">
      <form onSubmit={applyFilters} className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div className="mb-3 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <h2 className="text-lg font-semibold text-slate-800">Analytics Filters</h2>
            <p className="text-sm text-slate-500">Filter all dashboard analytics by programme and graduation year.</p>
          </div>
          <div className="flex gap-2">
            <button
              type="button"
              onClick={downloadCsv}
              className="rounded-md bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200"
            >
              Download CSV
            </button>
            <button
              type="button"
              onClick={downloadPdf}
              className="rounded-md bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-700"
            >
              Download PDF
            </button>
          </div>
        </div>
        <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
          <select
            name="programme"
            value={draftFilters.programme}
            onChange={handleFilterChange}
            className="rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500"
          >
            <option value="">All programmes</option>
            {filterOptions.programmes.map((programme) => (
              <option key={programme} value={programme}>
                {programme}
              </option>
            ))}
          </select>
          <select
            name="graduationYear"
            value={draftFilters.graduationYear}
            onChange={handleFilterChange}
            className="rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500"
          >
            <option value="">All graduation years</option>
            {filterOptions.graduationYears.map((year) => (
              <option key={year} value={year}>
                {year}
              </option>
            ))}
          </select>
          <div className="flex gap-2">
            <button className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
              Apply
            </button>
            <button
              type="button"
              onClick={resetFilters}
              className="rounded-md bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
            >
              Reset
            </button>
          </div>
        </div>
      </form>

      <section className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <KPIBox title="Total Alumni" value={data.kpi?.total_alumni} icon={icons.alumni} />
        <KPIBox title="Active Bidders" value={data.kpi?.active_bidders} icon={icons.bidders} />
        <KPIBox title="Bids This Month" value={data.kpi?.bids_this_month} icon={icons.bids} />
        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
          <div className="flex items-start justify-between gap-3">
            <div>
              <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Featured Profile</p>
              <p className="mt-2 text-xl font-semibold leading-tight text-slate-900">{featuredProfileName || "None"}</p>
              <p className="mt-1 text-xs text-slate-500">Bid: {featuredBidAmount}</p>
            </div>
            <button
              type="button"
              disabled={!data.featuredAlumnus}
              onClick={() => setShowFeaturedModal(true)}
            className="rounded-xl bg-indigo-50 p-2 text-indigo-700 ring-1 ring-indigo-100 transition hover:bg-indigo-600 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
              title="View featured profile"
            >
              <svg aria-hidden="true" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" />
                <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
              </svg>
              <span className="sr-only">View featured profile</span>
            </button>
          </div>
        </div>
      </section>

      {showFeaturedModal && data.featuredAlumnus ? (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
          onClick={() => setShowFeaturedModal(false)}
        >
          <div
            className="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white p-5 shadow-lg ring-1 ring-slate-200"
            onClick={(event) => event.stopPropagation()}
          >
            <div className="mb-4 flex items-start justify-between gap-4">
              <div>
                <h3 className="text-xl font-semibold text-slate-800">{featuredProfileName || "Featured Alumnus"}</h3>
                <p className="mt-1 text-sm text-slate-600">Bid Amount: {featuredBidAmount}</p>
                <p className="mt-2 text-sm text-slate-700">{data.featuredAlumnus.bio || "No biography available."}</p>
                <p className="mt-1 text-sm text-slate-700">LinkedIn: {data.featuredAlumnus.linkedin_url || "-"}</p>
              </div>
              <button
                type="button"
                onClick={() => setShowFeaturedModal(false)}
                className="rounded-md bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700 hover:bg-slate-200"
              >
                Close
              </button>
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
              {profileSections.map((section) => (
                <div key={section.key} className="rounded-lg border border-slate-200 p-4">
                  <h4 className="mb-2 font-medium text-slate-800">{section.title}</h4>
                  <ul className="space-y-1 text-sm text-slate-600">
                    {(data.featuredAlumnus[section.key] || []).length ? (
                      data.featuredAlumnus[section.key].map((item) => <li key={item.id}>{section.render(item)}</li>)
                    ) : (
                      <li>-</li>
                    )}
                  </ul>
                </div>
              ))}
            </div>
          </div>
        </div>
      ) : null}

      <section className="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <ChartCard title="Most Common Job Titles (Pie)" onDownload={() => downloadChartImage(jobTitlesRef, "job-titles.png")}>
          {charts.jobTitles.labels.length ? <Pie ref={jobTitlesRef} data={charts.jobTitles} options={circularChartOptions} /> : <p className="text-sm text-slate-500">No data available.</p>}
        </ChartCard>

        <ChartCard title="Top Employers (Doughnut)" onDownload={() => downloadChartImage(employersRef, "top-employers.png")}>
          {charts.employers.labels.length ? <Doughnut ref={employersRef} data={charts.employers} options={circularChartOptions} /> : <p className="text-sm text-slate-500">No data available.</p>}
        </ChartCard>

        <div className="xl:col-span-2">
          <ChartCard title="Curriculum Skills Gap by Programme (Bar)" onDownload={() => downloadChartImage(curriculumSkillsRef, "curriculum-skills-gap-programme.png")}>
            {charts.curriculumSkills.labels.length ? <Bar ref={curriculumSkillsRef} data={charts.curriculumSkills} options={chartOptions} /> : <p className="text-sm text-slate-500">No data available.</p>}
          </ChartCard>
        </div>

        <div className="xl:col-span-2">
          <ChartCard title="Alumni by Programme (Bar)" onDownload={() => downloadChartImage(programmeRef, "alumni-programme.png")}>
            {charts.programme.labels.length ? <Bar ref={programmeRef} data={charts.programme} options={chartOptions} /> : <p className="text-sm text-slate-500">No data available.</p>}
          </ChartCard>
        </div>

        <div className="xl:col-span-2">
          <ChartCard title="Certifications Over Time (Line)" onDownload={() => downloadChartImage(certificationsRef, "certifications-time.png")}>
            {charts.certifications.labels.length ? <Line ref={certificationsRef} data={charts.certifications} options={chartOptions} /> : <p className="text-sm text-slate-500">No data available.</p>}
          </ChartCard>
        </div>

        <div className="xl:col-span-2">
          <ChartCard title="Graduation Trends (Line)" onDownload={() => downloadChartImage(graduationRef, "graduation-trends.png")}>
            {charts.graduation.labels.length ? <Line ref={graduationRef} data={charts.graduation} options={chartOptions} /> : <p className="text-sm text-slate-500">No data available.</p>}
          </ChartCard>
        </div>
      </section>
    </div>
  );
};

export default Dashboard;
