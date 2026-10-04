import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../../api/client';
import {
  Users,
  Server,
  Cpu,
  CheckCircle2,
  Activity
} from 'lucide-react';
import toast from 'react-hot-toast';

export const AdminDashboard = () => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchStats();
  }, []);

  const fetchStats = async () => {
    try {
      const res = await api.get('/admin/stats');
      setData(res.data);
    } catch (err) {
      toast.error('Failed to load admin stats.');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return <div style={{ color: 'var(--text-muted)', padding: '20px' }}>Loading system statistics...</div>;
  }

  const stats = data?.stats || {};
  const recentDbs = data?.recent_databases || [];
  const recentActivities = data?.recent_activities || [];
  const distribution = data?.database_distribution || [];

  return (
    <div>
      {/* Top Stats Row */}
      <div className="stats-grid">
        <div className="stat-card">
          <div className="stat-icon-box">
            <Users size={20} />
          </div>
          <div>
            <div className="stat-value">{stats.total_users || 0}</div>
            <div className="stat-label">Registered Users</div>
          </div>
        </div>

        <div className="stat-card">
          <div className="stat-icon-box">
            <Server size={20} />
          </div>
          <div>
            <div className="stat-value">{stats.total_databases || 0}</div>
            <div className="stat-label">Total Databases</div>
          </div>
        </div>

        <div className="stat-card">
          <div className="stat-icon-box">
            <CheckCircle2 size={20} />
          </div>
          <div>
            <div className="stat-value">{stats.active_databases || 0}</div>
            <div className="stat-label">Active Instances</div>
          </div>
        </div>

        <div className="stat-card">
          <div className="stat-icon-box">
            <Cpu size={20} />
          </div>
          <div>
            <div className="stat-value">{stats.supported_engines || 5} Engines</div>
            <div className="stat-label">MySQL, MariaDB, PG, Mongo, Redis</div>
          </div>
        </div>
      </div>

      {/* Grid: Recently Allocated Databases & Activity / Breakdown */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(340px, 1fr))', gap: '16px', marginBottom: '20px' }}>
        
        {/* Recently Allocated Databases */}
        <div className="card">
          <div className="card-header">
            <div>
              <h2 className="card-title">Recent Database Allocations</h2>
              <p className="card-subtitle">Latest provisioned instances across all users</p>
            </div>
            <Link to="/admin/databases" className="btn btn-secondary btn-sm">
              All Databases
            </Link>
          </div>

          {recentDbs.length === 0 ? (
            <div style={{ padding: '30px', textAlign: 'center', color: 'var(--text-muted)' }}>
              No database instances provisioned yet.
            </div>
          ) : (
            <div className="table-responsive">
              <table className="table">
                <thead>
                  <tr>
                    <th>Database</th>
                    <th>User</th>
                    <th>Engine</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  {recentDbs.map((db) => (
                    <tr key={db.id}>
                      <td>
                        <div style={{ fontWeight: 600 }}>{db.name}</div>
                        <div style={{ fontSize: '11px', color: 'var(--text-muted)', fontFamily: 'var(--font-mono)' }}>
                          {db.database_name}
                        </div>
                      </td>
                      <td>
                        <div>{db.user?.username || 'User'}</div>
                        <div style={{ fontSize: '11px', color: 'var(--text-muted)' }}>{db.user?.email}</div>
                      </td>
                      <td>
                        <span style={{ fontWeight: 600, textTransform: 'uppercase', fontSize: '11px', color: 'var(--text-main)' }}>
                          {db.type}
                        </span>
                      </td>
                      <td>
                        <span className={`badge ${
                          db.status === 'active' ? 'badge-success' :
                          db.status === 'suspended' ? 'badge-warning' : 'badge-danger'
                        }`}>
                          {db.status}
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>

        {/* Database Distribution & System Feed */}
        <div className="card">
          <div className="card-header">
            <div>
              <h2 className="card-title">Engine Distribution</h2>
              <p className="card-subtitle">Active databases by software engine</p>
            </div>
          </div>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '8px', marginBottom: '18px' }}>
            {distribution.length === 0 ? (
              <div style={{ color: 'var(--text-muted)', fontSize: '12.5px' }}>No active engines provisioned yet.</div>
            ) : (
              distribution.map((item, idx) => (
                <div key={idx} style={{
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                  background: 'var(--bg-input)',
                  padding: '8px 12px',
                  borderRadius: '4px',
                  border: '1px solid var(--border-color)'
                }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <Server size={14} color="var(--text-main)" />
                    <span style={{ fontWeight: 600, textTransform: 'uppercase', fontSize: '12px' }}>{item.type}</span>
                  </div>
                  <span className="badge badge-success">{item.count} Active</span>
                </div>
              ))
            )}
          </div>

          <div className="card-header" style={{ marginTop: '14px', paddingTop: '10px' }}>
            <h3 className="card-title" style={{ fontSize: '13.5px' }}>System Audit Feed</h3>
          </div>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
            {recentActivities.map((act) => (
              <div key={act.id} style={{ fontSize: '11.5px', display: 'flex', justifyContent: 'space-between', color: 'var(--text-muted)' }}>
                <span>
                  <strong style={{ color: 'var(--text-main)' }}>{act.action}:</strong> {act.description}
                </span>
                <span style={{ fontSize: '11px', color: 'var(--text-dim)', flexShrink: 0, marginLeft: '8px' }}>
                  {new Date(act.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                </span>
              </div>
            ))}
          </div>
        </div>

      </div>
    </div>
  );
};

export default AdminDashboard;
