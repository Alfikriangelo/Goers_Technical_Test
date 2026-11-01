import React from 'react';
import { LogOut, Plus } from 'lucide-react';

function IndexMark() {
  return (
    <svg width="26" height="26" viewBox="0 0 26 26" fill="none" aria-hidden="true">
      <path d="M3 6h20M3 13h13M3 20h8" stroke="currentColor" strokeWidth="1.4" />
      <circle cx="21" cy="19" r="2.6" fill="#b1502a" />
    </svg>
  );
}

export default function Navbar({
  activeView,
  setActiveView,
  onOpenAddModal,
  isAdmin = false,
  admin = null,
  onLogout,
}) {
  return (
    <header className="site-header">
      <div className="header-inner">
        <div className="wordmark">
          <span className="wordmark-mark">
            <IndexMark />
          </span>
          <span className="wordmark-text">Goers</span>
          <span className="wordmark-sub">Resto Index</span>
        </div>

        <nav className="view-switch" aria-label="View">
          <button
            type="button"
            className={`view-tab ${activeView === 'customer' ? 'active' : ''}`}
            aria-current={activeView === 'customer' ? 'page' : undefined}
            onClick={() => setActiveView('customer')}
          >
            The index
          </button>
          <button
            type="button"
            className={`view-tab ${activeView === 'admin' ? 'active' : ''}`}
            aria-current={activeView === 'admin' ? 'page' : undefined}
            onClick={() => setActiveView('admin')}
          >
            Admin
          </button>
        </nav>

        <div className="header-actions">
          {isAdmin ? (
            <>
              <span className="session-note mono" title={admin?.email || 'Signed in as admin'}>
                {admin?.name || 'Admin'}
              </span>
              <button type="button" className="btn btn-quiet btn-sm" onClick={onOpenAddModal}>
                <Plus size={15} strokeWidth={1.6} />
                Add restaurant
              </button>
              <button
                type="button"
                className="btn-icon"
                onClick={onLogout}
                title="Sign out"
                aria-label="Sign out of the admin session"
              >
                <LogOut size={15} strokeWidth={1.6} />
              </button>
            </>
          ) : (
            <button
              type="button"
              className="btn btn-quiet btn-sm"
              onClick={() => setActiveView('admin')}
            >
              Admin sign in
            </button>
          )}
        </div>
      </div>
    </header>
  );
}
