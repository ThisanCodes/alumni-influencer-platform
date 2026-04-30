const ChartCard = ({ title, children, onDownload }) => {
  return (
    <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div className="mb-4 flex items-center justify-between gap-2">
        <h3 className="text-sm font-semibold text-slate-800">{title}</h3>
        {onDownload ? (
          <button
            onClick={onDownload}
            className="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-indigo-600 hover:text-white"
          >
            Download Image
          </button>
        ) : null}
      </div>
      <div className="h-72">{children}</div>
    </div>
  );
};

export default ChartCard;
