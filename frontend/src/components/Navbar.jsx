import React, { useState, useEffect } from 'react';
import { Utensils, Clock, ShieldCheck, Sparkles, Plus } from 'lucide-react';

export default function Navbar({ activeView, setActiveView, onOpenAddModal }) {
  const [currentTime, setCurrentTime] = useState(new Date());

  useEffect(() => {
    const timer = setInterval(() => setCurrentTime(new Date()), 1000);
    return () => clearInterval(timer);
  }, []);

  const formattedDay = currentTime.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
  const formattedTime = currentTime.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

  return (
    <header className="navbar">
      <div className="navbar-inner">
        <div className="logo-badge">
          <div className="logo-icon">
            <Utensils size={22} />
          </div>
          <div className="logo-text">
            <h1>Goers RestoFinder</h1>
            <p>Smart Dining Availability & Operations Engine</p>
          </div>
        </div>

        {/* Live System Time Banner */}
        <div style={{
          display: 'flex',
          alignItems: 'center',
          gap: '0.6rem',
          background: 'rgba(15, 23, 42, 0.7)',
          padding: '0.5rem 1rem',
          borderRadius: '12px',
          border: '1px solid rgba(255, 255, 255, 0.08)',
          fontSize: '0.825rem',
          color: '#cbd5e1'
        }}>
          <Clock size={16} className="text-emerald-400" style={{ color: '#34d399' }} />
          <span>Live: <strong>{formattedDay}</strong> at <strong>{formattedTime}</strong></span>
        </div>

        {/* Actions & View Switcher */}
        <div className="nav-actions">
          <div className="view-tabs">
            <button
              className={`tab-btn ${activeView === 'customer' ? 'active' : ''}`}
              onClick={() => setActiveView('customer')}
            >
              <Sparkles size={16} />
              Explore Directory
            </button>
            <button
              className={`tab-btn ${activeView === 'admin' ? 'active' : ''}`}
              onClick={() => setActiveView('admin')}
            >
              <ShieldCheck size={16} />
              Admin Portal
            </button>
          </div>

          <button className="btn btn-primary btn-sm" onClick={onOpenAddModal}>
            <Plus size={16} />
            Add Restaurant
          </button>
        </div>
      </div>
    </header>
  );
}
