import React from 'react';
import { useAuth } from '../context/AuthContext';

export const Topbar = ({ title }) => {
  const { user } = useAuth();

  return (
    <header className="topbar">
      <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
        <h1 className="topbar-title">{title || 'Dashboard'}</h1>
        <span className="badge badge-success">
          Online
        </span>
      </div>

      <div className="topbar-actions">
        <div style={{
          fontSize: '12.5px',
          color: 'var(--text-muted)',
          display: 'flex',
          alignItems: 'center',
          gap: '6px'
        }}>
          <span>Welcome, <strong style={{ color: 'var(--text-main)' }}>{user?.name}</strong></span>
        </div>
      </div>
    </header>
  );
};

export default Topbar;
