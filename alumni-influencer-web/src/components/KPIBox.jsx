const KPIBox = ({ title, value, icon }) => {
  return (
    <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
      <div className="flex items-start justify-between gap-3">
        <div>
          <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{title}</p>
          <p className="mt-2 text-2xl font-semibold leading-tight text-slate-900">{value ?? 0}</p>
        </div>
        {icon ? (
          <div className="rounded-xl bg-indigo-50 p-2 text-indigo-700 ring-1 ring-indigo-100">
            {icon}
          </div>
        ) : null}
      </div>
    </div>
  );
};

export default KPIBox;
