import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../../api/client';
import {
  Server,
  CheckCircle2,
  Copy,
  ExternalLink,
  Plus,
  Cpu
} from 'lucide-react';
import toast from 'react-hot-toast';

export const UserDashboard = () => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchStats();
  }, []);

  const fetchStats = async () => {
    try {
      const res = await api.get('/user/dashboard');
      setData(res.data);
    } catch (err) {
      toast.error('Failed to load dashboard data');
    } finally {
      setLoading(false);
    }
  };

  const copyToClipboard = (text, label) => {
    navigator.clipboard.writeText(text);
    toast.success(`${label} copied!`);
  };

  if (loading) {
    return <div style={{ color: 'var(--text-muted)', padding: '20px' }}>Loading overview...</div>;
  }

  const stats = data?.stats || {};
  const recentDbs = data?.recent_databases || [];

  return (
    <div>
      {/* Top Metrics Row */}
      <div className="stats-grid">
        <div className="stat-card">
          <div className="stat-icon-box">
            <Server size={20} />
          </div>
          <div>
            <div className="stat-value">{stats.databases_count || 0}</div>
            <div className="stat-label">Total Databases</div>
          </div>
        </div>

        <div className="stat-card">
          <div className="stat-icon-box">
            <CheckCircle2 size={20} />
          </div>
          <div>
            <div className="stat-value">{stats.active_count || 0}</div>
            <div className="stat-label">Active Instances</div>
          </div>
        </div>

        <div className="stat-card">
          <div className="stat-icon-box">
            <Cpu size={20} />
          </div>
          <div>
            <div className="stat-value">5 Engines</div>
            <div className="stat-label">MySQL, MariaDB, PG, Mongo, Redis</div>
          </div>
        </div>
      </div>

      {/* Main Database Instances Section */}
      <div className="card">
        <div className="card-header">
          <div>
            <h2 className="card-title">Database Instances</h2>
            <p className="card-subtitle">Connection endpoints and server credentials</p>
          </div>
          <Link to="/user/databases" className="btn btn-primary btn-sm">
            <Plus size={14} /> New Database
          </Link>
        </div>

        {recentDbs.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '40px 10px', color: 'var(--text-muted)' }}>
            <Server size={36} style={{ margin: '0 auto 10px', opacity: 0.3 }} />
            <p>You have not provisioned any databases yet.</p>
            <Link to="/user/databases" className="btn btn-primary btn-sm" style={{ marginTop: '12px' }}>
              Create Your First Database
            </Link>
          </div>
        ) : (
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(320px, 1fr))', gap: '14px' }}>
            {recentDbs.map((db) => (
              <div
                key={db.id}
                style={{
                  background: 'var(--bg-input)',
                  border: '1px solid var(--border-color)',
                  borderRadius: '4px',
                  padding: '14px',
                }}
              >
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
                  <div style={{ fontWeight: 600, fontSize: '13.5px', color: 'var(--text-main)' }}>
                    {db.name}
                  </div>
                  <span className={`badge ${db.status === 'active' ? 'badge-success' : 'badge-danger'}`}>
                    {db.status}
                  </span>
                </div>

                <div style={{ fontSize: '11.5px', color: 'var(--text-muted)', marginBottom: '10px' }}>
                  Engine: <strong style={{ color: 'var(--text-main)', textTransform: 'uppercase' }}>{db.type}</strong> | Host: <code>{db.host}:{db.port}</code>
                </div>

                <div className="mono-box" style={{ marginBottom: '8px' }}>
                  <span style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                    {db.connection_string}
                  </span>
                  <button
                    onClick={() => copyToClipboard(db.connection_string, 'Connection URI')}
                    title="Copy Connection String"
                    style={{ color: 'var(--text-main)', flexShrink: 0 }}
                  >
                    <Copy size={13} />
                  </button>
                </div>

                <div style={{ display: 'flex', gap: '8px', marginTop: '10px' }}>
                  <Link to="/user/databases" className="btn btn-secondary btn-sm" style={{ flex: 1 }}>
                    Credentials
                  </Link>
                  {db.phpmyadmin_url && (
                    <a
                      href={db.phpmyadmin_url}
                      target="_blank"
                      rel="noreferrer"
                      className="btn btn-secondary btn-sm"
                      style={{ display: 'flex', alignItems: 'center', gap: '4px' }}
                    >
                      <ExternalLink size={12} /> phpMyAdmin
                    </a>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default UserDashboard;
