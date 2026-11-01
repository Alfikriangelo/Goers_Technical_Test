import React, { useState } from 'react';
import { AlertCircle, ArrowRight, LockKeyhole } from 'lucide-react';

export default function AdminLogin({ onSubmit, busy, error }) {
  const [email, setEmail] = useState('admin@goers.co');
  const [password, setPassword] = useState('');

  const handleSubmit = (event) => {
    event.preventDefault();
    onSubmit({ email, password });
  };

  return (
    <section className="auth-panel" aria-labelledby="auth-title">
      <div className="auth-copy">
        <p className="eyebrow">Admin access</p>
        <h2 className="index-title" id="auth-title">
          The ledger is read-only in public.
        </h2>
        <p className="auth-note">
          Adding, editing, and deleting restaurant records requires a Sanctum token. Sign in with
          the seeded admin account to unlock the write actions.
        </p>
        <dl className="auth-meta">
          <div>
            <dt className="stat-label">Seeded account</dt>
            <dd className="mono">admin@goers.co</dd>
          </div>
          <div>
            <dt className="stat-label">Seeded password</dt>
            <dd className="mono">password</dd>
          </div>
        </dl>
      </div>

      <form className="auth-form" onSubmit={handleSubmit}>
        {error && (
          <div className="form-error" role="alert">
            <AlertCircle size={16} strokeWidth={1.6} style={{ flex: 'none', marginTop: 1 }} />
            <span>{error}</span>
          </div>
        )}

        <div className="form-group">
          <label className="form-label" htmlFor="auth-email">
            Email
          </label>
          <input
            id="auth-email"
            type="email"
            className="form-input"
            autoComplete="username"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            required
          />
        </div>

        <div className="form-group">
          <label className="form-label" htmlFor="auth-password">
            Password
          </label>
          <input
            id="auth-password"
            type="password"
            className="form-input"
            autoComplete="current-password"
            placeholder="••••••••"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            required
          />
        </div>

        <button type="submit" className="btn btn-primary" disabled={busy}>
          <LockKeyhole size={15} strokeWidth={1.6} />
          {busy ? 'Signing in…' : 'Sign in'}
          {!busy && <ArrowRight size={15} strokeWidth={1.6} />}
        </button>

        <p className="auth-disclaimer">
          Public browsing and search stay open. Only write endpoints are protected.
        </p>
      </form>
    </section>
  );
}
