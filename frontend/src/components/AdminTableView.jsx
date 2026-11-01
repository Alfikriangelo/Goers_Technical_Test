import React from 'react';
import { Plus, Star, Edit3, Trash2 } from 'lucide-react';

export default function AdminTableView({
  restaurants,
  onOpenAddModal,
  onEdit,
  onDelete,
  onViewSchedule,
}) {
  return (
    <section className="admin-panel" aria-label="Restaurant records">
      <div className="admin-panel-head">
        <p className="admin-panel-note">
          Every row is a restaurant record plus its structured weekly shifts. Add, edit, or remove a
          record and the availability engine updates immediately.
        </p>

        <button type="button" className="btn btn-quiet" onClick={onOpenAddModal}>
          <Plus size={16} strokeWidth={1.6} />
          Add restaurant
        </button>
      </div>

      <div className="table-scroll">
        <table className="admin-table">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Restaurant</th>
              <th scope="col">Operating hours</th>
              <th scope="col">Rating</th>
              <th scope="col">Status</th>
              <th scope="col" style={{ textAlign: 'right' }}>
                Actions
              </th>
            </tr>
          </thead>
          <tbody>
            {restaurants.length === 0 ? (
              <tr>
                <td colSpan={6} style={{ padding: '2.5rem 0', color: 'var(--muted)' }}>
                  No records match this query.
                </td>
              </tr>
            ) : (
              restaurants.map((item, i) => (
                <tr
                  key={item.id}
                  style={{ '--i': Math.min(i, 12), cursor: 'pointer' }}
                  onClick={() => onViewSchedule(item)}
                  title="Click to view weekly schedule"
                >
                  <td>
                    <span className="admin-id">#{String(item.id).padStart(3, '0')}</span>
                  </td>
                  <td>
                    <div className="admin-name">{item.name}</div>
                    <div className="admin-cuisine">{item.cuisine_type || 'General'}</div>
                  </td>
                  <td>
                    <div className="admin-hours">{item.formatted_schedule}</div>
                    <div className="admin-hours-meta">
                      {item.open_days_count ?? '—'}/7 days · {item.shifts_per_week ?? 0} services
                    </div>
                    <button
                      type="button"
                      className="link-btn"
                      onClick={(e) => {
                        e.stopPropagation();
                        onViewSchedule(item);
                      }}
                    >
                      Shift breakdown
                    </button>
                  </td>
                  <td>
                    <span className="row-rating">
                      <Star size={12} strokeWidth={1.4} fill="#b1502a" color="#b1502a" aria-hidden="true" />
                      {Number(item.rating ?? 4.5).toFixed(1)}
                    </span>
                  </td>
                  <td>
                    <span className={`row-status ${item.is_open_now ? 'open' : ''}`}>
                      <span className={`dot ${item.is_open_now ? 'open' : ''}`} aria-hidden="true" />
                      {item.is_open_now ? 'Open now' : 'Closed now'}
                    </span>
                  </td>
                  <td>
                    <div className="admin-actions">
                      <button
                        type="button"
                        className="btn-icon"
                        onClick={(e) => {
                          e.stopPropagation();
                          onEdit(item);
                        }}
                        aria-label={`Edit ${item.name}`}
                        title={`Edit ${item.name}`}
                      >
                        <Edit3 size={15} strokeWidth={1.6} />
                      </button>
                      <button
                        type="button"
                        className="btn-icon danger"
                        onClick={(e) => {
                          e.stopPropagation();
                          onDelete(item);
                        }}
                        aria-label={`Delete ${item.name}`}
                        title={`Delete ${item.name}`}
                      >
                        <Trash2 size={15} strokeWidth={1.6} />
                      </button>
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </section>
  );
}
