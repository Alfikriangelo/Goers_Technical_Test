import React from 'react';
import { MapPin, Clock, Star, Edit3, Trash2, Calendar } from 'lucide-react';

export default function RestaurantCard({
  restaurant,
  onViewSchedule,
  onEdit,
  onDelete,
  isAdmin = false,
}) {
  return (
    <div className="resto-card">
      {/* Top Banner with Cuisine and Open Status */}
      <div className="card-header-banner">
        <div className="banner-pattern"></div>
        <span className="cuisine-badge">
          {restaurant.cuisine_type || 'Dining'}
        </span>

        {restaurant.is_open_now ? (
          <span className="status-badge open">
            <span className="pulse-dot"></span>
            Open Now
          </span>
        ) : (
          <span className="status-badge closed">
            Closed
          </span>
        )}
      </div>

      {/* Main Content */}
      <div className="card-body">
        <h3 className="resto-name">{restaurant.name}</h3>

        <p className="resto-address">
          <MapPin size={14} style={{ color: '#818cf8', flexShrink: 0 }} />
          <span>{restaurant.address || 'San Francisco, CA'}</span>
        </p>

        {restaurant.description && (
          <p style={{ fontSize: '0.8rem', color: '#94a3b8', lineHeight: 1.4 }}>
            {restaurant.description}
          </p>
        )}

        {/* Operating Hours Box */}
        <div className="schedule-box">
          <div className="schedule-header">
            <span style={{ display: 'flex', alignItems: 'center', gap: '0.35rem' }}>
              <Clock size={13} style={{ color: '#6366f1' }} />
              OPERATING HOURS
            </span>
            <button
              onClick={() => onViewSchedule(restaurant)}
              style={{
                background: 'transparent',
                border: 'none',
                color: '#818cf8',
                cursor: 'pointer',
                fontSize: '0.75rem',
                fontWeight: 600,
                display: 'flex',
                alignItems: 'center',
                gap: '0.2rem'
              }}
              title="View daily breakdown"
            >
              <Calendar size={12} />
              Details
            </button>
          </div>
          <p className="schedule-raw-text">
            {restaurant.formatted_schedule || restaurant.raw_schedule}
          </p>
        </div>
      </div>

      {/* Footer with Rating & Admin Actions */}
      <div className="card-footer">
        <div className="rating-badge">
          <Star size={15} fill="#fbbf24" stroke="#fbbf24" />
          <span>{Number(restaurant.rating || 4.5).toFixed(1)}</span>
        </div>

        {isAdmin ? (
          <div style={{ display: 'flex', gap: '0.5rem' }}>
            <button
              className="btn btn-secondary btn-sm"
              onClick={() => onEdit(restaurant)}
              title="Edit Restaurant"
            >
              <Edit3 size={14} />
              Edit
            </button>
            <button
              className="btn btn-danger btn-sm"
              onClick={() => onDelete(restaurant)}
              title="Delete Restaurant"
            >
              <Trash2 size={14} />
              Delete
            </button>
          </div>
        ) : (
          <button
            className="btn btn-secondary btn-sm"
            onClick={() => onViewSchedule(restaurant)}
          >
            Weekly Schedule
          </button>
        )}
      </div>
    </div>
  );
}
