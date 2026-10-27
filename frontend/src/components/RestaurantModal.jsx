import React, { useState, useEffect } from 'react';
import { X, Save, Sparkles, AlertCircle } from 'lucide-react';

export default function RestaurantModal({ isOpen, onClose, onSave, initialData = null }) {
  const [formData, setFormData] = useState({
    name: '',
    raw_schedule: '',
    cuisine_type: '',
    address: '',
    rating: 4.5,
    description: '',
  });

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (initialData) {
      setFormData({
        name: initialData.name || '',
        raw_schedule: initialData.raw_schedule || initialData.formatted_schedule || '',
        cuisine_type: initialData.cuisine_type || '',
        address: initialData.address || '',
        rating: initialData.rating || 4.5,
        description: initialData.description || '',
      });
    } else {
      setFormData({
        name: '',
        raw_schedule: 'Mon-Sun 11:30 am - 10 pm',
        cuisine_type: 'Contemporary Dining',
        address: 'San Francisco, CA',
        rating: 4.5,
        description: '',
      });
    }
    setError(null);
  }, [initialData, isOpen]);

  if (!isOpen) return null;

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!formData.name.trim()) {
      setError('Restaurant name is required');
      return;
    }

    setLoading(true);
    setError(null);

    try {
      await onSave(formData, initialData?.id);
      onClose();
    } catch (err) {
      const msg = err.response?.data?.message || err.message || 'Failed to save restaurant';
      setError(msg);
    } finally {
      setLoading(false);
    }
  };

  const sampleSchedules = [
    'Mon-Sun 11 am - 10 pm',
    'Mon-Fri 11:30 am - 10 pm / Sat-Sun 5 pm - 11 pm',
    'Mon-Thu, Sun 11:30 am - 10 pm / Fri-Sat 11:30 am - 11 pm',
    'Mon-Sun 11 am - 4 am', // midnight rollover example!
  ];

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-content" onClick={(e) => e.stopPropagation()}>
        <div className="modal-header">
          <h2 style={{ fontSize: '1.25rem', color: '#f8fafc' }}>
            {initialData ? 'Edit Restaurant' : 'Add New Restaurant'}
          </h2>
          <button className="close-btn" onClick={onClose}>
            <X size={20} />
          </button>
        </div>

        {error && (
          <div style={{
            background: 'rgba(244, 63, 94, 0.15)',
            border: '1px solid rgba(244, 63, 94, 0.3)',
            borderRadius: '12px',
            padding: '0.75rem 1rem',
            color: '#fb7185',
            fontSize: '0.85rem',
            marginBottom: '1rem',
            display: 'flex',
            alignItems: 'center',
            gap: '0.5rem',
          }}>
            <AlertCircle size={16} />
            <span>{error}</span>
          </div>
        )}

        <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
          {/* Restaurant Name */}
          <div className="form-group">
            <label className="form-label" htmlFor="modal-name">
              Restaurant Name *
            </label>
            <input
              id="modal-name"
              type="text"
              className="form-input"
              placeholder="e.g. Goers Signature Bistro"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              required
            />
          </div>

          {/* Operating Hours */}
          <div className="form-group">
            <label className="form-label" htmlFor="modal-schedule">
              Operating Hours (Natural Language Schedule) *
            </label>
            <input
              id="modal-schedule"
              type="text"
              className="form-input"
              placeholder='e.g. "Mon-Fri 11 am - 10 pm / Sat-Sun 12 pm - 11 pm"'
              value={formData.raw_schedule}
              onChange={(e) => setFormData({ ...formData, raw_schedule: e.target.value })}
            />
            <div style={{ marginTop: '0.35rem' }}>
              <span style={{ fontSize: '0.75rem', color: '#64748b', display: 'block', marginBottom: '0.2rem' }}>
                Quick format presets:
              </span>
              <div style={{ display: 'flex', gap: '0.35rem', flexWrap: 'wrap' }}>
                {sampleSchedules.map((sample, idx) => (
                  <button
                    key={idx}
                    type="button"
                    style={{
                      background: 'rgba(255, 255, 255, 0.05)',
                      border: '1px solid rgba(255, 255, 255, 0.1)',
                      color: '#94a3b8',
                      fontSize: '0.72rem',
                      padding: '0.2rem 0.5rem',
                      borderRadius: '6px',
                      cursor: 'pointer',
                    }}
                    onClick={() => setFormData({ ...formData, raw_schedule: sample })}
                  >
                    {sample}
                  </button>
                ))}
              </div>
            </div>
          </div>

          {/* Cuisine & Rating */}
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
            <div className="form-group">
              <label className="form-label" htmlFor="modal-cuisine">
                Cuisine Type
              </label>
              <input
                id="modal-cuisine"
                type="text"
                className="form-input"
                placeholder="e.g. Japanese, Italian, Cafe"
                value={formData.cuisine_type}
                onChange={(e) => setFormData({ ...formData, cuisine_type: e.target.value })}
              />
            </div>

            <div className="form-group">
              <label className="form-label" htmlFor="modal-rating">
                Rating (1 - 5)
              </label>
              <input
                id="modal-rating"
                type="number"
                step="0.1"
                min="1"
                max="5"
                className="form-input"
                value={formData.rating}
                onChange={(e) => setFormData({ ...formData, rating: parseFloat(e.target.value) || 4.5 })}
              />
            </div>
          </div>

          {/* Address */}
          <div className="form-group">
            <label className="form-label" htmlFor="modal-address">
              Address / Location
            </label>
            <input
              id="modal-address"
              type="text"
              className="form-input"
              placeholder="e.g. 123 Market St, San Francisco, CA"
              value={formData.address}
              onChange={(e) => setFormData({ ...formData, address: e.target.value })}
            />
          </div>

          {/* Description */}
          <div className="form-group">
            <label className="form-label" htmlFor="modal-desc">
              Description / Notes
            </label>
            <textarea
              id="modal-desc"
              rows={2}
              className="form-input"
              placeholder="Optional notes or details about the restaurant"
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
            />
          </div>

          {/* Buttons */}
          <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '0.75rem', marginTop: '1rem' }}>
            <button type="button" className="btn btn-secondary" onClick={onClose} disabled={loading}>
              Cancel
            </button>
            <button type="submit" className="btn btn-primary" disabled={loading}>
              <Save size={16} />
              {loading ? 'Saving...' : initialData ? 'Update Restaurant' : 'Create Restaurant'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
