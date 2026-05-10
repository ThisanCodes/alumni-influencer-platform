import { useEffect, useState } from "react";
import api from "../services/api";
import Loader from "../components/Loader";
import { useNotification } from "../components/NotificationProvider";
import { getApiErrorMessage } from "../services/apiError";

const statusLabel = (bid) => {
  if (bid.outcome) return bid.outcome;
  if (Number(bid.status) === 2) return "CANCELLED";
  if (Number(bid.slot_is_active) === 1) return "PENDING";
  return "SUBMITTED";
};

const Bidding = () => {
  const notification = useNotification();
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [amount, setAmount] = useState("");
  const [updates, setUpdates] = useState({});
  const [history, setHistory] = useState([]);
  const [limit, setLimit] = useState(null);

  const loadBidding = async () => {
    setLoading(true);
    try {
      const [historyResponse, limitResponse] = await Promise.all([
        api.get("/bids/history"),
        api.get("/bids/monthly-limit"),
      ]);
      const bids = historyResponse.data?.data || [];
      setHistory(Array.isArray(bids) ? bids : []);
      setLimit(limitResponse.data?.data || null);
    } catch (err) {
      const nextError = getApiErrorMessage(err, "Failed to load bidding details.");
      notification.error(nextError);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadBidding();
  }, []);

  const placeBid = async (event) => {
    event.preventDefault();
    setSubmitting(true);

    try {
      await api.post("/bids", { amount });
      setAmount("");
      notification.success("Bid placed successfully.");
      await loadBidding();
    } catch (err) {
      const nextError = getApiErrorMessage(err, "Could not place bid.");
      notification.error(nextError);
    } finally {
      setSubmitting(false);
    }
  };

  const updateBid = async (bid) => {
    const nextAmount = updates[bid.id];
    if (!nextAmount) return;

    try {
      await api.put(`/bids/${bid.id}`, { amount: nextAmount });
      setUpdates((prev) => ({ ...prev, [bid.id]: "" }));
      notification.success("Bid updated successfully.");
      await loadBidding();
    } catch (err) {
      const nextError = getApiErrorMessage(err, "Could not update bid.");
      notification.error(nextError);
    }
  };

  const cancelBid = async (bid) => {
    if (!window.confirm("Cancel this active bid?")) {
      return;
    }

    try {
      await api.post(`/bids/${bid.id}/cancel`);
      notification.success("Bid cancelled successfully.");
      await loadBidding();
    } catch (err) {
      const nextError = getApiErrorMessage(err, "Could not cancel bid.");
      notification.error(nextError);
    }
  };

  if (loading) return <Loader text="Loading bidding details..." />;

  return (
    <div className="space-y-5 p-5">
      <div>
        <h2 className="text-xl font-semibold text-slate-800">Bidding</h2>
        <p className="text-sm text-slate-500">Place blind bids for the Alumni of the Day slot and track your own outcomes.</p>
      </div>

      <section className="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div className="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <p className="text-sm text-slate-500">Monthly Wins</p>
          <p className="mt-2 text-2xl font-bold text-slate-900">{limit?.label || "0/3"}</p>
          <p className="mt-1 text-xs text-slate-500">
            {limit?.event_bonus_applied ? "Event bonus applied." : "Participating in an event can unlock one extra monthly win."}
          </p>
        </div>
        <div className="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <p className="text-sm text-slate-500">Remaining Slots</p>
          <p className="mt-2 text-2xl font-bold text-slate-900">{limit?.remaining ?? "-"}</p>
          <p className="mt-1 text-xs text-slate-500">{limit?.limit_reached ? "Monthly limit reached." : "You can still compete this month."}</p>
        </div>
        <form onSubmit={placeBid} className="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <label className="text-sm font-medium text-slate-700">
            New Bid Amount
            <input
              required
              min="0.01"
              step="0.01"
              type="number"
              value={amount}
              onChange={(e) => setAmount(e.target.value)}
              className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"
            />
          </label>
          <button disabled={submitting} className="mt-3 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">
            {submitting ? "Submitting..." : "Place Blind Bid"}
          </button>
        </form>
      </section>

      <section className="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <h3 className="mb-4 font-semibold text-slate-800">Bid History</h3>
        <div className="space-y-3">
          {history.length ? (
            history.map((bid) => {
              const label = statusLabel(bid);
              const canManage = label === "PENDING" && Number(bid.status) !== 2;

              return (
                <div key={bid.id} className="rounded-lg border border-slate-200 p-4">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <p className="font-medium text-slate-800">{bid.slot_name || "Bid Slot"}</p>
                      <p className="text-sm text-slate-500">Date: {bid.slot_date || "-"}</p>
                      <p className="text-sm text-slate-500">Your amount: {bid.amount}</p>
                    </div>
                    <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{label}</span>
                  </div>

                  {canManage ? (
                    <div className="mt-3 flex flex-wrap gap-2">
                      <input
                        min="0.01"
                        step="0.01"
                        type="number"
                        placeholder="Higher amount"
                        value={updates[bid.id] || ""}
                        onChange={(e) => setUpdates((prev) => ({ ...prev, [bid.id]: e.target.value }))}
                        className="rounded-md border border-slate-300 px-3 py-2 text-sm"
                      />
                      <button onClick={() => updateBid(bid)} className="rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500">
                        Update
                      </button>
                      <button onClick={() => cancelBid(bid)} className="rounded-md bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">
                        Cancel
                      </button>
                    </div>
                  ) : null}
                </div>
              );
            })
          ) : (
            <p className="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No bids submitted yet.</p>
          )}
        </div>
      </section>
    </div>
  );
};

export default Bidding;
