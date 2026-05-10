import clientApi from "./clientApi";

export const analyticsService = {
  getKpi: async (params) => (await clientApi.get("/analytics/kpi", { params })).data,
  getAlumniByProgramme: async (params) => (await clientApi.get("/analytics/alumni-by-programme", { params })).data,
  getCertificationsOverTime: async (params) => (await clientApi.get("/analytics/certifications-over-time", { params })).data,
  getCurriculumSkillsGapByProgramme: async (params) => (await clientApi.get("/analytics/curriculum-skills-gap-by-programme", { params })).data,
  getTopEmployers: async (params) => (await clientApi.get("/analytics/top-employers", { params })).data,
  getJobTitles: async (params) => (await clientApi.get("/analytics/job-titles", { params })).data,
  getGraduationTrends: async (params) => (await clientApi.get("/analytics/graduation-trends", { params })).data,
  getFilterOptions: async () => (await clientApi.get("/profile/filter-options")).data,
  getFeaturedAlumnus: async () =>
    (
      await clientApi.get("/featured-alumnus", {
        headers: {
          "X-API-Key": import.meta.env.VITE_FEATURED_ALUMNUS_API_KEY,
        },
      })
    ).data,
};
