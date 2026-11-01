import React from 'react';
import { Search } from 'lucide-react';
import { toLocalDate } from '../services/dates';

const PRESETS = [
  { key: 'open_now', label: 'Open now' },
  { key: 'today', label: 'Today' },
  { key: 'tomorrow', label: 'Tomorrow' },
  { key: 'lunch', label: 'Lunch 12:00' },
  { key: 'dinner', label: 'Dinner 19:00' },
  { key: 'late_night', label: 'Late night 01:00' },
];

export default function FilterPanel({
  filters,
  onFilterChange,
  onResetFilters,
  onQuickPreset,
}) {
  const today = toLocalDate();
  const tomorrowDate = new Date();
  tomorrowDate.setDate(tomorrowDate.getDate() + 1);
  const tomorrow = toLocalDate(tomorrowDate);

  const isActive = (key) => {
    switch (key) {
      case 'open_now':
        return filters.open_now;
      case 'today':
        return !filters.open_now && filters.date === today;
      case 'tomorrow':
        return !filters.open_now && filters.date === tomorrow;
      case 'lunch':
        return !filters.open_now && filters.time === '12:00';
      case 'dinner':
        return !filters.open_now && filters.time === '19:00';
      case 'late_night':
        return !filters.open_now && filters.time === '01:00';
      default:
        return false;
    }
  };

  return (
    <div className="query">
      <div className="query-grid">
        <label className="field field-search" htmlFor="filter-name">
          <span className="field-label">Restaurant name</span>
          <span className="field-control">
            <Search size={15} strokeWidth={1.6} className="field-icon" />
            <input
              id="filter-name"
              type="text"
              className="field-input"
              placeholder="Cheesecake, sushi, Indian…"
              value={filters.name}
              onChange={(e) => onFilterChange('name', e.target.value)}
            />
          </span>
        </label>

        <label className="field" htmlFor="filter-date">
          <span className="field-label">Date</span>
          <input
            id="filter-date"
            type="date"
            className="field-input"
            value={filters.date}
            onChange={(e) => {
              onFilterChange('date', e.target.value);
              if (filters.open_now) onFilterChange('open_now', false);
            }}
          />
        </label>

        <label className="field" htmlFor="filter-time">
          <span className="field-label">Hour</span>
          <input
            id="filter-time"
            type="time"
            className="field-input"
            value={filters.time}
            onChange={(e) => {
              onFilterChange('time', e.target.value);
              if (filters.open_now) onFilterChange('open_now', false);
            }}
          />
        </label>

        <div className="query-actions">
          <button id="btn-reset-filters" type="button" className="btn btn-quiet" onClick={onResetFilters}>
            Clear Filter
          </button>
        </div>
      </div>

      <div className="presets">
        <span className="presets-label">Shortcuts</span>
        {PRESETS.map((preset) => (
          <button
            key={preset.key}
            type="button"
            className={`tag ${isActive(preset.key) ? 'active' : ''}`}
            aria-pressed={isActive(preset.key)}
            onClick={() => onQuickPreset(preset.key)}
          >
            {preset.label}
          </button>
        ))}
      </div>
    </div>
  );
}
