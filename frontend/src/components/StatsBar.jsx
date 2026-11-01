import React from 'react';

export default function StatsBar({ stats, totalFiltered }) {
  const items = [
    { value: stats?.total_restaurants ?? '—', label: 'Restaurants indexed' },
    { value: stats?.open_now_count ?? '—', label: 'Open at index time' },
    { value: stats?.cuisines_count ?? '—', label: 'Cuisine categories' },
    { value: totalFiltered ?? '—', label: 'Matching this query' },
  ];

  return (
    <div className="stats-strip">
      {items.map((item) => (
        <div className="stat-item" key={item.label}>
          <span className="stat-value">{item.value}</span>
          <span className="stat-label">{item.label}</span>
        </div>
      ))}
    </div>
  );
}
