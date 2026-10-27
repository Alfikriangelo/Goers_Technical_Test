import React from 'react';
import { Store, Flame, UtensilsCrossed, CalendarClock } from 'lucide-react';

export default function StatsBar({ stats, totalFiltered }) {
  return (
    <div className="stats-bar">
      <div className="stat-card">
        <div className="stat-icon indigo">
          <Store size={24} />
        </div>
        <div className="stat-info">
          <h4>{stats?.total_restaurants ?? 52}</h4>
          <p>Total Registered</p>
        </div>
      </div>

      <div className="stat-card">
        <div className="stat-icon emerald">
          <Flame size={24} />
        </div>
        <div className="stat-info">
          <h4>{stats?.open_now_count ?? 0}</h4>
          <p>Currently Open Now</p>
        </div>
      </div>

      <div className="stat-card">
        <div className="stat-icon amber">
          <UtensilsCrossed size={24} />
        </div>
        <div className="stat-info">
          <h4>{stats?.cuisines_count ?? 18}+</h4>
          <p>Cuisine Categories</p>
        </div>
      </div>

      <div className="stat-card">
        <div className="stat-icon cyan">
          <CalendarClock size={24} />
        </div>
        <div className="stat-info">
          <h4>{totalFiltered}</h4>
          <p>Matching Filters</p>
        </div>
      </div>
    </div>
  );
}
