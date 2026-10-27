import React from 'react';
import { Edit3, Trash2, Plus, Calendar, Star, CheckCircle, XCircle } from 'lucide-react';

export default function AdminTableView({
  restaurants,
  onOpenAddModal,
  onEdit,
  onDelete,
  onViewSchedule,
}) {
  return (
    <div className="table-container">
      <div style={{
        padding: '1.25rem 1.5rem',
        borderBottom: '1px solid rgba(255, 255, 255, 0.08)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        flexWrap: 'wrap',
        gap: '1rem',
      }}>
        <div>
          <h2 style={{ fontSize: '1.2rem', color: '#f8fafc' }}>
            Restaurant Administration Directory
          </h2>
          <p style={{ fontSize: '0.8rem', color: '#94a3b8' }}>
            Manage registered restaurants, operational shifts, and schedule parsing data.
          </p>
        </div>

        <button className="btn btn-primary btn-sm" onClick={onOpenAddModal}>
          <Plus size={16} />
          Add Restaurant
        </button>
      </div>

      <div style={{ overflowX: 'auto' }}>
        <table className="admin-table">
          <thead>
            <tr>
              <th style={{ width: '60px' }}>#ID</th>
              <th>Name & Cuisine</th>
              <th>Operating Hours (Raw / Formatted)</th>
              <th>Rating</th>
              <th>Live Status</th>
              <th style={{ textAlign: 'right' }}>Actions</th>
            </tr>
          </thead>
          <tbody>
            {restaurants.length === 0 ? (
              <tr>
                <td colSpan={6} style={{ textAlign: 'center', padding: '3rem', color: '#64748b' }}>
                  No restaurants found matching your criteria.
                </td>
              </tr>
            ) : (
              restaurants.map((item) => (
                <tr key={item.id}>
                  <td style={{ color: '#64748b', fontWeight: 600 }}>#{item.id}</td>
                  <td>
                    <div style={{ fontWeight: 700, color: '#f8fafc' }}>{item.name}</div>
                    <div style={{ fontSize: '0.78rem', color: '#818cf8' }}>
                      {item.cuisine_type || 'General'}
                    </div>
                  </td>
                  <td>
                    <div style={{ fontSize: '0.825rem', color: '#cbd5e1', maxWidth: '380px' }}>
                      {item.raw_schedule || item.formatted_schedule}
                    </div>
                    <button
                      onClick={() => onViewSchedule(item)}
                      style={{
                        background: 'transparent',
                        border: 'none',
                        color: '#a5b4fc',
                        fontSize: '0.75rem',
                        cursor: 'pointer',
                        padding: 0,
                        marginTop: '0.2rem',
                        display: 'inline-flex',
                        alignItems: 'center',
                        gap: '0.25rem',
                      }}
                    >
                      <Calendar size={12} />
                      View Shifts Table
                    </button>
                  </td>
                  <td>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.25rem', color: '#fbbf24', fontWeight: 600 }}>
                      <Star size={14} fill="#fbbf24" stroke="#fbbf24" />
                      <span>{Number(item.rating || 4.5).toFixed(1)}</span>
                    </div>
                  </td>
                  <td>
                    {item.is_open_now ? (
                      <span style={{
                        background: 'rgba(16, 185, 129, 0.15)',
                        color: '#34d399',
                        border: '1px solid rgba(16, 185, 129, 0.3)',
                        borderRadius: '20px',
                        padding: '0.25rem 0.65rem',
                        fontSize: '0.75rem',
                        fontWeight: 600,
                        display: 'inline-flex',
                        alignItems: 'center',
                        gap: '0.3rem',
                      }}>
                        <CheckCircle size={12} /> Open
                      </span>
                    ) : (
                      <span style={{
                        background: 'rgba(244, 63, 94, 0.12)',
                        color: '#fb7185',
                        border: '1px solid rgba(244, 63, 94, 0.25)',
                        borderRadius: '20px',
                        padding: '0.25rem 0.65rem',
                        fontSize: '0.75rem',
                        fontWeight: 600,
                        display: 'inline-flex',
                        alignItems: 'center',
                        gap: '0.3rem',
                      }}>
                        <XCircle size={12} /> Closed
                      </span>
                    )}
                  </td>
                  <td style={{ textAlign: 'right' }}>
                    <div style={{ display: 'inline-flex', gap: '0.5rem' }}>
                      <button
                        className="btn btn-secondary btn-sm"
                        onClick={() => onEdit(item)}
                        title="Edit Restaurant"
                      >
                        <Edit3 size={14} />
                      </button>
                      <button
                        className="btn btn-danger btn-sm"
                        onClick={() => onDelete(item)}
                        title="Delete Restaurant"
                      >
                        <Trash2 size={14} />
                      </button>
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
