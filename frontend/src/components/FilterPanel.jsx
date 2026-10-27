import React from 'react';
import { Search, Calendar, Clock, RotateCcw, Sparkles, Moon, Sun, Utensils } from 'lucide-react';

export default function FilterPanel({
  filters,
  onFilterChange,
  onResetFilters,
  onQuickPreset,
}) {
  const getTodayDateString = (offsetDays = 0) => {
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    return d.toISOString().split('T')[0];
  };

  return (
    <div className="filter-panel">
      <div className="filter-grid">
        {/* 1. Filter Nama Restoran */}
        <div className="form-group">
          <label className="form-label" htmlFor="filter-name">
            <Search size={15} />
            Search Restaurant Name
          </label>
          <input
            id="filter-name"
            type="text"
            className="form-input"
            placeholder="e.g. Cheesecake, Sushi, Indian..."
            value={filters.name}
            onChange={(e) => onFilterChange('name', e.target.value)}
          />
        </div>

        {/* 2. Filter Tanggal */}
        <div className="form-group">
          <label className="form-label" htmlFor="filter-date">
            <Calendar size={15} />
            Specific Date
          </label>
          <input
            id="filter-date"
            type="date"
            className="form-input"
            value={filters.date}
            onChange={(e) => {
              onFilterChange('date', e.target.value);
              // If date is set manually, disable open_now flag
              if (filters.open_now) onFilterChange('open_now', false);
            }}
          />
        </div>

        {/* 3. Filter Jam */}
        <div className="form-group">
          <label className="form-label" htmlFor="filter-time">
            <Clock size={15} />
            Specific Time
          </label>
          <input
            id="filter-time"
            type="time"
            className="form-input"
            value={filters.time}
            onChange={(e) => {
              onFilterChange('time', e.target.value);
              if (filters.open_now) onFilterChange('open_now', false);
            }}
          />
        </div>

        {/* Reset Button */}
        <div className="form-group">
          <button
            id="btn-reset-filters"
            className="btn btn-secondary"
            onClick={onResetFilters}
            title="Clear all filters"
          >
            <RotateCcw size={16} />
            Reset
          </button>
        </div>
      </div>

      {/* Quick Filter Presets */}
      <div className="quick-pills">
        <span style={{ fontSize: '0.8rem', color: '#64748b', fontWeight: 600, marginRight: '0.25rem' }}>
          Quick Filters:
        </span>

        {/* Open Right Now */}
        <button
          className={`pill-btn ${filters.open_now ? 'active' : ''}`}
          onClick={() => onQuickPreset('open_now')}
        >
          <Sparkles size={13} />
          Open Right Now
        </button>

        {/* Today */}
        <button
          className={`pill-btn ${filters.date === getTodayDateString(0) && !filters.open_now ? 'active' : ''}`}
          onClick={() => onQuickPreset('today')}
        >
          <Calendar size={13} />
          Today
        </button>

        {/* Tomorrow */}
        <button
          className={`pill-btn ${filters.date === getTodayDateString(1) ? 'active' : ''}`}
          onClick={() => onQuickPreset('tomorrow')}
        >
          <Calendar size={13} />
          Tomorrow
        </button>

        {/* Lunch 12:00 */}
        <button
          className={`pill-btn ${filters.time === '12:00' ? 'active' : ''}`}
          onClick={() => onQuickPreset('lunch')}
        >
          <Sun size={13} />
          Lunch (12:00)
        </button>

        {/* Dinner 19:00 */}
        <button
          className={`pill-btn ${filters.time === '19:00' ? 'active' : ''}`}
          onClick={() => onQuickPreset('dinner')}
        >
          <Utensils size={13} />
          Dinner (19:00)
        </button>

        {/* Late Night 01:00 (Midnight Crossover Test) */}
        <button
          className={`pill-btn ${filters.time === '01:00' ? 'active' : ''}`}
          onClick={() => onQuickPreset('late_night')}
        >
          <Moon size={13} />
          Late Night (01:00 AM)
        </button>
      </div>
    </div>
  );
}
