const Loader = ({ text = "Loading..." }) => {
  return (
    <div className="flex min-h-48 items-center justify-center">
      <div className="flex items-center gap-3 text-slate-600">
        <div className="h-6 w-6 animate-spin rounded-full border-2 border-slate-300 border-t-indigo-600" />
        <span>{text}</span>
      </div>
    </div>
  );
};

export default Loader;
