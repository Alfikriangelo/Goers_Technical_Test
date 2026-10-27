import React, { useState, useEffect, useCallback } from 'react';
import Navbar from './components/Navbar';
import StatsBar from './components/StatsBar';
import FilterPanel from './components/FilterPanel';
import RestaurantCard from './components/RestaurantCard';
import AdminTableView from './components/AdminTableView';
import RestaurantModal from './components/RestaurantModal';
import ScheduleModal from './components/ScheduleModal';
import { RestaurantAPI } from './services/api';
import { Loader2, ChevronLeft, ChevronRight, CheckCircle2, AlertTriangle, Utensils } from 'lucide-react';

export default function App() {
  const [restaurants, setRestaurants] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [activeView, setActiveView] = useState('customer'); // 'customer' | 'admin'

  // Query Filter State
  const [filters, setFilters] = useState({
    name: '',
    date: '',
    time: '',
    open_now: false,
    page: 1,
    per_page: 12,
  });

  const [paginationMeta, setPaginationMeta] = useState({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    from: 0,
    to: 0,
  });

  // Modal States
  const [restaurantModal, setRestaurantModal] = useState({ isOpen: false, initialData: null });
  const [scheduleModal, setScheduleModal] = useState({ isOpen: false, restaurant: null });

  // Toast notification
  const [toast, setToast] = useState(null);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  // Fetch Stats
  const loadStats = async () => {
    try {
      const data = await RestaurantAPI.stats();
      setStats(data);
    } catch (err) {
      console.error('Failed to load stats:', err);
    }
  };

  // Fetch Restaurants with current filters
  const loadRestaurants = useCallback(async () => {
    setLoading(true);
    try {
      const params = {
        page: filters.page,
        per_page: activeView === 'admin' ? 15 : filters.per_page,
      };

      if (filters.name) params.name = filters.name;
      if (filters.open_now) {
        params.open_now = true;
      } else {
        if (filters.date) params.date = filters.date;
        if (filters.time) params.time = filters.time;
      }

      const res = await RestaurantAPI.list(params);
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
      console.error('Failed to load restaurants:', err);
      showToast('Error loading restaurants. Ensure Laravel server is running.', 'error');
    } finally {
      setLoading(false);
    }
  }, [filters, activeView]);

  useEffect(() => {
    loadStats();
  }, []);

  // Debounced search & filter reload
  useEffect(() => {
    const handler = setTimeout(() => {
      loadRestaurants();
    }, 250);
    return () => clearTimeout(handler);
  }, [loadRestaurants]);

  // Handle filter updates
  const handleFilterChange = (key, value) => {
    setFilters((prev) => ({
      ...prev,
      [key]: value,
      page: 1, // reset page on filter change
    }));
  };

  const handleResetFilters = () => {
    setFilters({
      name: '',
      date: '',
      time: '',
      open_now: false,
      page: 1,
      per_page: 12,
    });
    showToast('Filters cleared');
  };

  const handleQuickPreset = (preset) => {
    const today = new Date().toISOString().split('T')[0];
    const tomorrowDate = new Date();
    tomorrowDate.setDate(tomorrowDate.getDate() + 1);
    const tomorrow = tomorrowDate.toISOString().split('T')[0];

    switch (preset) {
      case 'open_now':
        setFilters((prev) => ({
          ...prev,
          open_now: true,
          date: '',
          time: '',
          page: 1,
        }));
        break;
      case 'today':
        setFilters((prev) => ({
          ...prev,
          open_now: false,
          date: today,
          page: 1,
        }));
        break;
      case 'tomorrow':
        setFilters((prev) => ({
          ...prev,
          open_now: false,
          date: tomorrow,
          page: 1,
        }));
        break;
      case 'lunch':
        setFilters((prev) => ({
          ...prev,
          open_now: false,
          time: '12:00',
          page: 1,
        }));
        break;
      case 'dinner':
        setFilters((prev) => ({
          ...prev,
          open_now: false,
          time: '19:00',
          page: 1,
        }));
        break;
      case 'late_night':
        setFilters((prev) => ({
          ...prev,
          open_now: false,
          time: '01:00',
          page: 1,
        }));
        break;
      default:
        break;
    }
  };

  // CRUD actions
  const handleOpenAddModal = () => {
    setRestaurantModal({ isOpen: true, initialData: null });
  };

  const handleOpenEditModal = (restaurant) => {
    setRestaurantModal({ isOpen: true, initialData: restaurant });
  };

  const handleSaveRestaurant = async (formData, id) => {
    if (id) {
      await RestaurantAPI.update(id, formData);
      showToast(`Restaurant "${formData.name}" successfully updated`);
    } else {
      await RestaurantAPI.create(formData);
      showToast(`Restaurant "${formData.name}" added successfully`);
    }
    loadRestaurants();
    loadStats();
  };

  const handleDeleteRestaurant = async (restaurant) => {
    if (!window.confirm(`Are you sure you want to delete "${restaurant.name}"?`)) {
      return;
    }

    try {
      await RestaurantAPI.delete(restaurant.id);
      showToast(`"${restaurant.name}" deleted successfully`);
      loadRestaurants();
      loadStats();
    } catch (err) {
      showToast(err.response?.data?.message || 'Failed to delete restaurant', 'error');
    }
  };

  const handleViewSchedule = (restaurant) => {
    setScheduleModal({ isOpen: true, restaurant });
  };

  return (
    <div>
      <Navbar
        activeView={activeView}
        setActiveView={setActiveView}
        onOpenAddModal={handleOpenAddModal}
      />

      <main className="app-container">
        {/* Statistics Cards */}
        <StatsBar stats={stats} totalFiltered={paginationMeta.total} />

        {/* Search & Filter Bar */}
        <FilterPanel
          filters={filters}
          onFilterChange={handleFilterChange}
          onResetFilters={handleResetFilters}
          onQuickPreset={handleQuickPreset}
        />

        {/* Content View: Customer Card Grid vs Admin Table */}
        {loading ? (
          <div style={{ textAlign: 'center', padding: '5rem 0', color: '#818cf8' }}>
            <Loader2 size={36} className="animate-spin" style={{ animation: 'spin 1s linear infinite', margin: '0 auto 1rem' }} />
            <p style={{ color: '#94a3b8', fontSize: '0.9rem' }}>Searching operating restaurants...</p>
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
            <div className="empty-icon">🍽️</div>
            <h3 style={{ fontSize: '1.25rem', marginBottom: '0.5rem', color: '#f8fafc' }}>
              No Restaurants Match Your Criteria
            </h3>
            <p style={{ color: '#94a3b8', maxWidth: '440px', margin: '0 auto 1.5rem', fontSize: '0.875rem' }}>
              Try searching with another name, choosing a different time (e.g. Lunch 12:00 or Dinner 19:00), or clicking reset.
            </p>
            <button className="btn btn-primary btn-sm" onClick={handleResetFilters}>
              Reset Filters
            </button>
          </div>
        ) : (
          <div className="resto-grid">
            {restaurants.map((resto) => (
              <RestaurantCard
                key={resto.id}
                restaurant={resto}
                onViewSchedule={handleViewSchedule}
                onEdit={handleOpenEditModal}
                onDelete={handleDeleteRestaurant}
                isAdmin={false}
              />
            ))}
          </div>
        )}

        {/* Pagination Bar */}
        {!loading && paginationMeta.total > 0 && (
          <div className="pagination-bar">
            <span>
              Showing <strong>{paginationMeta.from}</strong> to <strong>{paginationMeta.to}</strong> of <strong>{paginationMeta.total}</strong> restaurants
            </span>

            <div className="pagination-nav">
              <button
                className="btn btn-secondary btn-sm"
                disabled={filters.page <= 1}
                onClick={() => setFilters((p) => ({ ...p, page: p.page - 1 }))}
              >
                <ChevronLeft size={16} />
                Previous
              </button>

              <span style={{ padding: '0 0.5rem', fontWeight: 600, color: '#f8fafc' }}>
                Page {paginationMeta.currentPage} / {paginationMeta.lastPage}
              </span>

              <button
                className="btn btn-secondary btn-sm"
                disabled={filters.page >= paginationMeta.lastPage}
                onClick={() => setFilters((p) => ({ ...p, page: p.page + 1 }))}
              >
                Next
                <ChevronRight size={16} />
              </button>
            </div>
          </div>
        )}
      </main>

      {/* Add / Edit Restaurant Modal */}
      <RestaurantModal
        isOpen={restaurantModal.isOpen}
        onClose={() => setRestaurantModal({ isOpen: false, initialData: null })}
        onSave={handleSaveRestaurant}
        initialData={restaurantModal.initialData}
      />

      {/* Full Weekly Schedule Modal */}
      <ScheduleModal
        restaurant={scheduleModal.restaurant}
        onClose={() => setScheduleModal({ isOpen: false, restaurant: null })}
      />

      {/* Toast Notification */}
      {toast && (
        <div style={{
          position: 'fixed',
          bottom: '1.5rem',
          right: '1.5rem',
          zIndex: 1000,
          background: toast.type === 'error' ? 'rgba(244, 63, 94, 0.95)' : 'rgba(16, 185, 129, 0.95)',
          color: '#ffffff',
          padding: '0.85rem 1.25rem',
          borderRadius: '12px',
          boxShadow: '0 10px 25px -5px rgba(0,0,0,0.5)',
          display: 'flex',
          alignItems: 'center',
          gap: '0.5rem',
          fontSize: '0.875rem',
          fontWeight: 600,
          animation: 'fadeIn 0.2s ease-out',
        }}>
          {toast.type === 'error' ? <AlertTriangle size={18} /> : <CheckCircle2 size={18} />}
          <span>{toast.message}</span>
        </div>
      )}

      {/* Keyframe animation for spinning loader */}
      <style>{`
        @keyframes spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );
}
