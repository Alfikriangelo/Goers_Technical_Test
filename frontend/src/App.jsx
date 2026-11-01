import React, { useCallback, useEffect, useRef, useState } from 'react';
import Navbar from './components/Navbar';
import StatsBar from './components/StatsBar';
import FilterPanel from './components/FilterPanel';
import RestaurantCard from './components/RestaurantCard';
import AdminTableView from './components/AdminTableView';
import AdminLogin from './components/AdminLogin';
import RestaurantModal from './components/RestaurantModal';
import ScheduleModal from './components/ScheduleModal';
import {
  AuthAPI,
  RestaurantAPI,
  describeApiError,
  isCanceledRequest,
} from './services/api';
import { toLocalDate } from './services/dates';
import { ChevronLeft, ChevronRight, CheckCircle2, AlertTriangle, RotateCcw } from 'lucide-react';
import diningImage from './assets/dining.jpg';

const DEFAULT_FILTERS = {
  name: '',
  date: '',
  time: '',
  open_now: false,
  page: 1,
  per_page: 12,
  sort_by: 'name',
  sort_order: 'asc',
};

const SORTS = [
  { key: 'name', label: 'Name', sort_by: 'name', sort_order: 'asc' },
  { key: 'rating', label: 'Rating', sort_by: 'rating', sort_order: 'desc' },
  { key: 'newest', label: 'Newest', sort_by: 'created_at', sort_order: 'desc' },
];

function SkeletonList() {
  return (
    <div className="resto-list" aria-hidden="true">
      {Array.from({ length: 6 }).map((_, index) => (
        <div className="resto-row skeleton-row" key={index}>
          <span className="sk sk-index" />
          <span className="sk sk-main" />
          <span className="sk sk-hours" />
          <span className="sk sk-rating" />
          <span className="sk sk-status" />
          <span className="sk sk-actions" />
        </div>
      ))}
    </div>
  );
}

export default function App() {
  const [restaurants, setRestaurants] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState(null);
  const [activeView, setActiveView] = useState('customer');

  const [filters, setFilters] = useState(DEFAULT_FILTERS);

  const [paginationMeta, setPaginationMeta] = useState({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    from: 0,
    to: 0,
  });

  const [admin, setAdmin] = useState(() => AuthAPI.getCurrentUser());
  const [authBusy, setAuthBusy] = useState(false);
  const [authError, setAuthError] = useState(null);

  const [restaurantModal, setRestaurantModal] = useState({ isOpen: false, initialData: null });
  const [scheduleModal, setScheduleModal] = useState({ isOpen: false, restaurant: null });
  const [toast, setToast] = useState(null);

  const toastTimer = useRef(null);
  const listAbort = useRef(null);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    window.clearTimeout(toastTimer.current);
    toastTimer.current = window.setTimeout(() => setToast(null), 4000);
  };

  useEffect(() => () => window.clearTimeout(toastTimer.current), []);

  const loadStats = async (signal) => {
    try {
      const data = await RestaurantAPI.stats(signal);
      setStats(data);
    } catch (err) {
      if (isCanceledRequest(err)) return;
      console.error('Failed to load stats:', err);
    }
  };

  const loadRestaurants = useCallback(async () => {
    listAbort.current?.abort();
    const controller = new AbortController();
    listAbort.current = controller;

    setLoading(true);
    setLoadError(null);

    try {
      const params = {
        page: filters.page,
        per_page: activeView === 'admin' ? 15 : filters.per_page,
        sort_by: filters.sort_by,
        sort_order: filters.sort_order,
      };

      if (filters.name) params.name = filters.name;
      if (filters.open_now) {
        params.open_now = true;
      } else {
        if (filters.date) params.date = filters.date;
        if (filters.time) params.time = filters.time;
      }

      const res = await RestaurantAPI.list(params, controller.signal);
      setRestaurants(res.data || []);

      if (res.meta) {
        setPaginationMeta({
          currentPage: res.meta.current_page || 1,
          lastPage: res.meta.last_page || 1,
          total: res.meta.total || 0,
          from: res.meta.from || 0,
          to: res.meta.to || 0,
        });
      }
    } catch (err) {
      if (isCanceledRequest(err)) return;
      console.error('Failed to load restaurants:', err);
      setRestaurants([]);
      setLoadError(describeApiError(err, 'The index could not be loaded.'));
    } finally {
      if (listAbort.current === controller) {
        setLoading(false);
      }
    }
  }, [filters, activeView]);

  useEffect(() => {
    const controller = new AbortController();
    loadStats(controller.signal);
    return () => controller.abort();
  }, []);

  useEffect(() => {
    const handler = window.setTimeout(() => {
      loadRestaurants();
    }, 250);
    return () => window.clearTimeout(handler);
  }, [loadRestaurants]);

  useEffect(() => () => listAbort.current?.abort(), []);

  useEffect(() => {
    const onUnauthorized = () => {
      setAdmin(null);
      setRestaurantModal({ isOpen: false, initialData: null });
      showToast('Your admin session expired. Sign in again to keep editing.', 'error');
    };
    window.addEventListener('goers:unauthorized', onUnauthorized);
    return () => window.removeEventListener('goers:unauthorized', onUnauthorized);
  }, []);

  const handleFilterChange = (key, value) => {
    setFilters((prev) => ({
      ...prev,
      [key]: value,
      page: 1,
    }));
  };

  const handleResetFilters = () => {
    setFilters(DEFAULT_FILTERS);
    showToast('The query was cleared.');
  };

  const handleQuickPreset = (preset) => {
    const today = toLocalDate();
    const tomorrowDate = new Date();
    tomorrowDate.setDate(tomorrowDate.getDate() + 1);
    const tomorrow = toLocalDate(tomorrowDate);

    switch (preset) {
      case 'open_now':
        setFilters((prev) => ({ ...prev, open_now: true, date: '', time: '', page: 1 }));
        break;
      case 'today':
        setFilters((prev) => ({ ...prev, open_now: false, date: today, time: '', page: 1 }));
        break;
      case 'tomorrow':
        setFilters((prev) => ({ ...prev, open_now: false, date: tomorrow, time: '', page: 1 }));
        break;
      case 'lunch':
        setFilters((prev) => ({ ...prev, open_now: false, time: '12:00', page: 1 }));
        break;
      case 'dinner':
        setFilters((prev) => ({ ...prev, open_now: false, time: '19:00', page: 1 }));
        break;
      case 'late_night':
        setFilters((prev) => ({ ...prev, open_now: false, time: '01:00', page: 1 }));
        break;
      default:
        break;
    }
  };

  const handleSort = (sort) => {
    setFilters((prev) => ({
      ...prev,
      sort_by: sort.sort_by,
      sort_order: sort.sort_order,
      page: 1,
    }));
  };

  const handleViewChange = (view) => {
    setActiveView(view);
    setFilters((prev) => ({ ...prev, page: 1 }));
  };

  const handleOpenAddModal = () => {
    if (!admin) {
      setActiveView('admin');
      showToast('Sign in as admin to add a restaurant.', 'error');
      return;
    }
    setRestaurantModal({ isOpen: true, initialData: null });
  };

  const handleOpenEditModal = (restaurant) => {
    if (!admin) {
      showToast('Sign in as admin to edit a restaurant.', 'error');
      return;
    }
    setRestaurantModal({ isOpen: true, initialData: restaurant });
  };

  const handleSaveRestaurant = async (formData, id) => {
    if (id) {
      await RestaurantAPI.update(id, formData);
      showToast(`“${formData.name}” was updated.`);
    } else {
      await RestaurantAPI.create(formData);
      showToast(`“${formData.name}” was added to the index.`);
    }
    loadRestaurants();
    loadStats();
  };

  const handleDeleteRestaurant = async (restaurant) => {
    if (!admin) {
      setActiveView('admin');
      showToast('Please sign in as admin to delete records.', 'error');
      return;
    }

    if (!window.confirm(`Delete “${restaurant.name}” and all of its shifts?`)) {
      return;
    }

    try {
      await RestaurantAPI.delete(restaurant.id);
      showToast(`“${restaurant.name}” was deleted.`);
      loadRestaurants();
      loadStats();
    } catch (err) {
      const { title, detail } = describeApiError(err, 'The record could not be deleted.');
      showToast(`${title} ${detail}`, 'error');
    }
  };

  const handleAdminLogin = async ({ email, password }) => {
    setAuthBusy(true);
    setAuthError(null);

    try {
      const data = await AuthAPI.login(email.trim(), password);
      setAdmin(data.user);
      showToast(`Signed in as ${data.user?.name || 'admin'}.`);
      loadRestaurants();
    } catch (err) {
      const { title, detail } = describeApiError(err, 'Sign-in failed.');
      setAuthError(title === detail ? title : `${title} ${detail}`);
    } finally {
      setAuthBusy(false);
    }
  };

  const handleAdminLogout = async () => {
    await AuthAPI.logout().catch(() => {});
    setAdmin(null);
    setActiveView('customer');
    showToast('Signed out of the admin session.');
  };

  const handleViewSchedule = (restaurant) => {
    setScheduleModal({ isOpen: true, restaurant });
  };

  const totalIndexed = stats?.total_restaurants ?? 0;
  const openNow = stats?.open_now_count ?? 0;
  const openPct = totalIndexed > 0 ? Math.round((openNow / totalIndexed) * 100) : 0;
  const currentSort =
    filters.sort_by === 'rating' ? 'rating' : filters.sort_by === 'created_at' ? 'newest' : 'name';
  const rowsPerPage = activeView === 'admin' ? 15 : filters.per_page;
  const showAdminLogin = activeView === 'admin' && !admin;

  return (
    <div>
      <Navbar
        activeView={activeView}
        setActiveView={handleViewChange}
        onOpenAddModal={handleOpenAddModal}
        isAdmin={Boolean(admin)}
        admin={admin}
        onLogout={handleAdminLogout}
      />

      <main className="shell app-container">
        <section className="hero" aria-labelledby="hero-title">
          <div className="hero-copy">
            <p className="eyebrow">Goers · San Francisco dining index</p>
            <h1 className="hero-title" id="hero-title">
              A table for every{' '}
              <span className="inline-plate" aria-hidden="true">
                <img src={diningImage} alt="" />
              </span>{' '}
              hour.
            </h1>
            <p className="hero-sub">
              Search {stats?.total_restaurants ?? 'the'} kitchens by name, date, or the exact hour
              you plan to eat. Service that runs past midnight is counted as open, not guessed.
            </p>
            <div className="hero-actions">
              <a className="btn btn-primary" href="#index">
                Browse the index
              </a>
              <span className="hero-note">Weekly shifts · Midnight-aware · No account needed</span>
            </div>
          </div>

          <aside className="brief" aria-label="Live availability">
            <div className="brief-head">
              <span className="eyebrow">Index time</span>
              <span className="mono eyebrow">{stats?.current_day ?? '—'}</span>
            </div>
            <div className="brief-figure">
              <span className="brief-count">{stats?.open_now_count ?? '—'}</span>
              <span className="brief-unit">
                kitchens open
                <br />
                right now
              </span>
            </div>
            <div
              className="brief-bar"
              role="img"
              aria-label={`${openPct} percent of indexed kitchens are open`}
            >
              <span style={{ width: `${openPct}%` }} />
            </div>
            <div className="brief-meta">
              <span className="mono">{stats?.current_time ?? '--:--'}</span>
              <span className="mono">
                {openPct}% of {stats?.total_restaurants ?? '—'}
              </span>
            </div>
            <p className="brief-note">
              Status is computed from each kitchen’s parsed weekly shifts, including service that
              crosses midnight. Times follow San Francisco.
            </p>
          </aside>
        </section>

        <StatsBar stats={stats} totalFiltered={paginationMeta.total} />

        <section className="index-section" id="index" aria-labelledby="index-title">
          <div className="index-head">
            <div>
              <p className="eyebrow">{activeView === 'admin' ? 'Operations' : 'Directory'}</p>
              <h2 className="index-title" id="index-title">
                {activeView === 'admin' ? 'Restaurant records' : 'The index'}
              </h2>
            </div>

            <div className="index-tools">
              {!showAdminLogin && !loadError && (
                <>
                  <span className="mono index-count">
                    {paginationMeta.total} {paginationMeta.total === 1 ? 'result' : 'results'}
                  </span>
                  <div className="sort-control" role="group" aria-label="Sort results">
                    {SORTS.map((sort) => (
                      <button
                        key={sort.key}
                        type="button"
                        className={`sort-btn ${currentSort === sort.key ? 'active' : ''}`}
                        aria-pressed={currentSort === sort.key}
                        onClick={() => handleSort(sort)}
                      >
                        {sort.label}
                      </button>
                    ))}
                  </div>
                </>
              )}
            </div>
          </div>

          <FilterPanel
            filters={filters}
            onFilterChange={handleFilterChange}
            onResetFilters={handleResetFilters}
            onQuickPreset={handleQuickPreset}
          />

          {showAdminLogin ? (
            <AdminLogin onSubmit={handleAdminLogin} busy={authBusy} error={authError} />
          ) : loadError ? (
            <div className="load-error" role="alert">
              <p className="eyebrow">Index unavailable</p>
              <h3>{loadError.title}</h3>
              <p>{loadError.detail}</p>
              <div className="load-error-actions">
                <button type="button" className="btn btn-primary" onClick={loadRestaurants}>
                  <RotateCcw size={15} strokeWidth={1.6} />
                  Retry the request
                </button>
                {loadError.status ? (
                  <span className="mono load-error-status">HTTP {loadError.status}</span>
                ) : null}
              </div>
            </div>
          ) : loading ? (
            <div aria-busy="true" aria-live="polite">
              <span className="eyebrow" style={{ display: 'block', marginBottom: '0.75rem' }}>
                Reading the index…
              </span>
              <SkeletonList />
            </div>
          ) : activeView === 'admin' ? (
            <AdminTableView
              restaurants={restaurants}
              onOpenAddModal={handleOpenAddModal}
              onEdit={handleOpenEditModal}
              onDelete={handleDeleteRestaurant}
              onViewSchedule={handleViewSchedule}
            />
          ) : restaurants.length === 0 ? (
            <div className="empty-state">
              <p className="eyebrow">No matches</p>
              <h3>Nothing is open for that query.</h3>
              <p>
                Try a shorter name, a different hour, or clear the filters. Late-night kitchens are
                included when their service crosses midnight.
              </p>
              <button type="button" className="btn btn-primary" onClick={handleResetFilters}>
                Reset the query
              </button>
            </div>
          ) : (
            <div className="resto-list">
              {restaurants.map((resto, index) => (
                <RestaurantCard
                  key={resto.id}
                  restaurant={resto}
                  serverDayName={stats?.current_day ?? null}
                  index={(paginationMeta.currentPage - 1) * rowsPerPage + index + 1}
                  order={index}
                  onViewSchedule={handleViewSchedule}
                  onEdit={handleOpenEditModal}
                  onDelete={handleDeleteRestaurant}
                  isAdmin={Boolean(admin)}
                />
              ))}
            </div>
          )}

          {!showAdminLogin && !loadError && !loading && paginationMeta.total > 0 && (
            <div className="pagination">
              <span className="pagination-meta">
                Showing {paginationMeta.from}–{paginationMeta.to} of {paginationMeta.total}
              </span>
              <div className="pagination-nav">
                <button
                  type="button"
                  className="btn btn-quiet btn-sm"
                  disabled={filters.page <= 1}
                  onClick={() => setFilters((prev) => ({ ...prev, page: prev.page - 1 }))}
                >
                  <ChevronLeft size={15} strokeWidth={1.6} />
                  Previous
                </button>
                <span className="pagination-page">
                  {paginationMeta.currentPage} / {paginationMeta.lastPage}
                </span>
                <button
                  type="button"
                  className="btn btn-quiet btn-sm"
                  disabled={filters.page >= paginationMeta.lastPage}
                  onClick={() => setFilters((prev) => ({ ...prev, page: prev.page + 1 }))}
                >
                  Next
                  <ChevronRight size={15} strokeWidth={1.6} />
                </button>
              </div>
            </div>
          )}
        </section>

        <footer className="site-footer">
          <span>Goers · Fullstack trial · Restaurant index</span>
          <span>Laravel 11 · React 19</span>
        </footer>
      </main>

      <RestaurantModal
        isOpen={restaurantModal.isOpen}
        onClose={() => setRestaurantModal({ isOpen: false, initialData: null })}
        onSave={handleSaveRestaurant}
        initialData={restaurantModal.initialData}
      />

      <ScheduleModal
        restaurant={scheduleModal.restaurant}
        onClose={() => setScheduleModal({ isOpen: false, restaurant: null })}
      />

      {toast && (
        <div className={`toast ${toast.type === 'error' ? 'error' : ''}`} role="status">
          <span className="toast-icon">
            {toast.type === 'error' ? (
              <AlertTriangle size={16} strokeWidth={1.6} />
            ) : (
              <CheckCircle2 size={16} strokeWidth={1.6} />
            )}
          </span>
          <span>{toast.message}</span>
        </div>
      )}
    </div>
  );
}
