import React, { useEffect, useState } from 'react';
import api from '../../api/client';
import {
  Server,
  Plus,
  Search,
  Key,
  Copy,
  Eye,
  EyeOff,
  Play,
  Pause,
  Trash2,
  X
} from 'lucide-react';
import toast from 'react-hot-toast';

export const AdminDatabases = () => {
  const [databases, setDatabases] = useState([]);
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [selectedDb, setSelectedDb] = useState(null);
  const [showPassword, setShowPassword] = useState(false);
  const [createModal, setCreateModal] = useState(false);
  const [creating, setCreating] = useState(false);

  const [form, setForm] = useState({
    user_id: '',
    name: '',
    type: 'mysql',
    database_name: '',
    database_username: '',
    database_password: '',
  });

  useEffect(() => {
    fetchDatabases();
    fetchUsers();
  }, [statusFilter]);

  const fetchDatabases = async () => {
    try {
      const res = await api.get('/admin/databases', {
        params: {
          search: search || undefined,
          status: statusFilter || undefined,
        }
      });
      setDatabases(res.data.databases.data || res.data.databases || []);
    } catch (err) {
      toast.error('Failed to load database instances.');
    } finally {
      setLoading(false);
    }
  };

  const fetchUsers = async () => {
    try {
      const res = await api.get('/admin/users');
      setUsers(res.data.users.data || res.data.users || []);
    } catch (e) {
      // Ignore
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    fetchDatabases();
  };

  const handleCreateDatabase = async (e) => {
    e.preventDefault();
    if (!form.user_id) {
      toast.error('Please select an owner user.');
      return;
    }

    setCreating(true);
    try {
      await api.post('/admin/databases', form);
      toast.success('Database allocated successfully.');
      setCreateModal(false);
      setForm({
        user_id: '',
        name: '',
        type: 'mysql',
        database_name: '',
        database_username: '',
        database_password: '',
      });
      fetchDatabases();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to allocate database.');
    } finally {
      setCreating(false);
    }
  };

  const handleSuspend = async (id) => {
    try {
      await api.patch(`/admin/databases/${id}/suspend`);
      toast.success('Database suspended.');
      fetchDatabases();
    } catch (err) {
      toast.error('Failed to suspend database.');
    }
  };

  const handleUnsuspend = async (id) => {
    try {
      await api.patch(`/admin/databases/${id}/unsuspend`);
      toast.success('Database unsuspended.');
      fetchDatabases();
    } catch (err) {
      toast.error('Failed to unsuspend database.');
    }
  };

  const handleTerminate = async (id, dbName) => {
    if (!window.confirm(`PERMANENT ACTION: Terminate and drop database "${dbName}"?`)) {
      return;
    }

    try {
      await api.delete(`/admin/databases/${id}`);
      toast.success('Database terminated.');
      fetchDatabases();
    } catch (err) {
      toast.error('Failed to terminate database.');
    }
  };

  const copyToClipboard = (text, label) => {
    navigator.clipboard.writeText(text);
    toast.success(`${label} copied!`);
  };

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
        <div>
          <h2 style={{ fontSize: '16px', fontWeight: 600 }}>All Deployed Databases</h2>
          <p style={{ fontSize: '12.5px', color: 'var(--text-muted)' }}>
            Supervise and allocate database instances across all user accounts
          </p>
        </div>
        <button onClick={() => setCreateModal(true)} className="btn btn-primary">
          <Plus size={15} /> New Database
        </button>
      </div>

      {/* Filter and Search Bar */}
      <div className="card" style={{ padding: '12px 14px', marginBottom: '14px' }}>
        <form onSubmit={handleSearch} style={{ display: 'flex', gap: '10px', flexWrap: 'wrap' }}>
          <div style={{ position: 'relative', flex: 1, minWidth: '200px' }}>
            <input
              type="text"
              className="form-control"
              placeholder="Search by instance, db name, username, or client..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>

          <select
            className="form-control"
            style={{ width: '150px' }}
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
          >
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="suspended">Suspended</option>
            <option value="terminated">Terminated</option>
          </select>

          <button type="submit" className="btn btn-secondary">
            <Search size={14} /> Filter
          </button>
        </form>
      </div>

      {/* Main Table */}
      <div className="card">
        {loading ? (
          <div style={{ padding: '30px', textAlign: 'center', color: 'var(--text-muted)' }}>Loading instances...</div>
        ) : databases.length === 0 ? (
          <div style={{ padding: '40px', textAlign: 'center', color: 'var(--text-muted)' }}>
            No databases match the query.
          </div>
        ) : (
          <div className="table-responsive">
            <table className="table">
              <thead>
                <tr>
                  <th>Instance Name</th>
                  <th>Client Owner</th>
                  <th>Engine</th>
                  <th>Endpoint</th>
                  <th>Database / User</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {databases.map((db) => (
                  <tr key={db.id}>
                    <td>
                      <div style={{ fontWeight: 600, color: 'var(--text-main)' }}>{db.name}</div>
                      <div style={{ fontSize: '11px', color: 'var(--text-dim)', fontFamily: 'var(--font-mono)' }}>
                        {db.uuid.substring(0, 16)}...
                      </div>
                    </td>
                    <td>
                      <div>{db.user?.username || 'Client'}</div>
                      <div style={{ fontSize: '11px', color: 'var(--text-muted)' }}>{db.user?.email}</div>
                    </td>
                    <td>
                      <span style={{ fontWeight: 600, textTransform: 'uppercase', fontSize: '11px', color: 'var(--text-main)' }}>
                        {db.type}
                      </span>
                    </td>
                    <td style={{ fontFamily: 'var(--font-mono)', fontSize: '12px' }}>
                      {db.host}:{db.port}
                    </td>
                    <td style={{ fontFamily: 'var(--font-mono)', fontSize: '12px' }}>
                      <div>{db.database_name}</div>
                      <div style={{ color: 'var(--text-muted)', fontSize: '11px' }}>u: {db.database_username}</div>
                    </td>
                    <td>
                      <span className={`badge ${
                        db.status === 'active' ? 'badge-success' :
                        db.status === 'suspended' ? 'badge-warning' : 'badge-danger'
                      }`}>
                        {db.status}
                      </span>
                    </td>
                    <td>
                      <div style={{ display: 'flex', gap: '6px' }}>
                        <button
                          onClick={() => {
                            setSelectedDb(db);
                            setShowPassword(false);
                          }}
                          className="btn btn-secondary btn-sm"
                          title="View Connection & Password"
                        >
                          <Key size={12} />
                        </button>

                        {db.status === 'active' ? (
                          <button
                            onClick={() => handleSuspend(db.id)}
                            className="btn btn-secondary btn-sm"
                            title="Suspend Access"
                          >
                            <Pause size={12} color="#f59e0b" />
                          </button>
                        ) : db.status === 'suspended' ? (
                          <button
                            onClick={() => handleUnsuspend(db.id)}
                            className="btn btn-secondary btn-sm"
                            title="Unsuspend Access"
                          >
                            <Play size={12} color="#10b981" />
                          </button>
                        ) : null}

                        {db.status !== 'terminated' && (
                          <button
                            onClick={() => handleTerminate(db.id, db.database_name)}
                            className="btn btn-danger btn-sm"
                            title="Terminate Database"
                          >
                            <Trash2 size={12} />
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Allocate Database Modal (Admin) */}
      {createModal && (
        <div className="modal-overlay" onClick={() => setCreateModal(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h3 className="modal-title">Allocate Database Instance</h3>
              <button onClick={() => setCreateModal(false)} style={{ color: 'var(--text-muted)' }}>
                <X size={16} />
              </button>
            </div>

            <form onSubmit={handleCreateDatabase}>
              <div className="form-group">
                <label className="form-label">Assign to User</label>
                <select
                  className="form-control"
                  value={form.user_id}
                  onChange={(e) => setForm({ ...form, user_id: e.target.value })}
                  required
                >
                  <option value="">Select User...</option>
                  {users.map(u => (
                    <option key={u.id} value={u.id}>{u.username} ({u.email})</option>
                  ))}
                </select>
              </div>

              <div className="form-group">
                <label className="form-label">Instance Label</label>
                <input
                  type="text"
                  className="form-control"
                  placeholder="e.g. Client Production DB"
                  value={form.name}
                  onChange={(e) => setForm({ ...form, name: e.target.value })}
                  required
                />
              </div>

              <div className="form-group">
                <label className="form-label">Database Engine</label>
                <select
                  className="form-control"
                  value={form.type}
                  onChange={(e) => setForm({ ...form, type: e.target.value })}
                >
                  <option value="mysql">MySQL 8.4</option>
                  <option value="mariadb">MariaDB 11</option>
                  <option value="postgresql">PostgreSQL 16</option>
                  <option value="mongodb">MongoDB 7</option>
                  <option value="redis">Redis Cache</option>
                </select>
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px' }}>
                <div className="form-group">
                  <label className="form-label">DB Name (Optional)</label>
                  <input
                    type="text"
                    className="form-control"
                    placeholder="Auto-generate"
                    value={form.database_name}
                    onChange={(e) => setForm({ ...form, database_name: e.target.value })}
                  />
                </div>
                <div className="form-group">
                  <label className="form-label">DB User (Optional)</label>
                  <input
                    type="text"
                    className="form-control"
                    placeholder="Auto-generate"
                    value={form.database_username}
                    onChange={(e) => setForm({ ...form, database_username: e.target.value })}
                  />
                </div>
              </div>

              <div className="modal-footer">
                <button type="button" className="btn btn-secondary" onClick={() => setCreateModal(false)}>
                  Cancel
                </button>
                <button type="submit" className="btn btn-primary" disabled={creating}>
                  {creating ? 'Allocating...' : 'Allocate Database'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Admin Credentials Inspection Modal */}
      {selectedDb && (
        <div className="modal-overlay" onClick={() => setSelectedDb(null)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h3 className="modal-title">Instance Credentials (Admin)</h3>
              <button onClick={() => setSelectedDb(null)} style={{ color: 'var(--text-muted)' }}>
                <X size={16} />
              </button>
            </div>

            <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
              <div>
                <label className="form-label">Client Owner</label>
                <input
                  type="text"
                  className="form-control"
                  value={`${selectedDb.user?.name} (${selectedDb.user?.email})`}
                  readOnly
                />
              </div>

              <div>
                <label className="form-label">Database Username & Password</label>
                <div style={{ display: 'flex', gap: '6px', marginBottom: '8px' }}>
                  <input type="text" className="form-control" style={{ fontFamily: 'var(--font-mono)' }} value={selectedDb.database_username} readOnly />
                  <button className="btn btn-secondary btn-sm" onClick={() => copyToClipboard(selectedDb.database_username, 'Username')}>
                    <Copy size={12} />
                  </button>
                </div>

                <div style={{ display: 'flex', gap: '6px' }}>
                  <input
                    type={showPassword ? 'text' : 'password'}
                    className="form-control"
                    style={{ fontFamily: 'var(--font-mono)' }}
                    value={selectedDb.password}
                    readOnly
                  />
                  <button
                    className="btn btn-secondary btn-sm"
                    onClick={() => setShowPassword(!showPassword)}
                  >
                    {showPassword ? <EyeOff size={13} /> : <Eye size={13} />}
                  </button>
                  <button className="btn btn-secondary btn-sm" onClick={() => copyToClipboard(selectedDb.password, 'Password')}>
                    <Copy size={12} />
                  </button>
                </div>
              </div>

              <div>
                <label className="form-label">Full Connection String URI</label>
                <div className="mono-box">
                  <span style={{ wordBreak: 'break-all' }}>{selectedDb.connection_string}</span>
                  <button onClick={() => copyToClipboard(selectedDb.connection_string, 'Connection URI')} style={{ color: 'var(--text-main)' }}>
                    <Copy size={13} />
                  </button>
                </div>
              </div>
            </div>

            <div className="modal-footer">
              <button type="button" className="btn btn-secondary" onClick={() => setSelectedDb(null)}>
                Close
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default AdminDatabases;
