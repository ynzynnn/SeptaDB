import React, { useEffect, useState } from 'react';
import api from '../../api/client';
import { Key, Plus, Trash2, Power, Copy, Check, Info, X } from 'lucide-react';
import toast from 'react-hot-toast';

export const AdminApiKeys = () => {
  const [keys, setKeys] = useState([]);
  const [loading, setLoading] = useState(true);
  const [createModal, setCreateModal] = useState(false);
  const [keyName, setKeyName] = useState('');
  const [createdToken, setCreatedToken] = useState(null);

  useEffect(() => {
    fetchKeys();
  }, []);

  const fetchKeys = async () => {
    try {
      const res = await api.get('/admin/api-keys');
      setKeys(res.data.api_keys);
    } catch (err) {
      toast.error('Failed to load API keys.');
    } finally {
      setLoading(false);
    }
  };

  const handleCreate = async (e) => {
    e.preventDefault();
    try {
      const res = await api.post('/admin/api-keys', { name: keyName });
      setCreatedToken(res.data.token);
      setKeyName('');
      setCreateModal(false);
      fetchKeys();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to generate key.');
    }
  };

  const handleToggle = async (id) => {
    try {
      await api.patch(`/admin/api-keys/${id}/toggle`);
      toast.success('API Key status updated.');
      fetchKeys();
    } catch (err) {
      toast.error('Failed to update API key status.');
    }
  };

  const handleDelete = async (id, name) => {
    if (!window.confirm(`Revoke and permanently delete API key "${name}"? Paymenter requests using this key will immediately fail.`)) {
      return;
    }

    try {
      await api.delete(`/admin/api-keys/${id}`);
      toast.success('API key revoked.');
      fetchKeys();
    } catch (err) {
      toast.error('Failed to revoke API key.');
    }
  };

  const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    toast.success('API Token copied to clipboard!');
  };

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
        <div>
          <h2 style={{ fontSize: '18px', fontWeight: 600 }}>Paymenter Integration & API Keys</h2>
          <p style={{ fontSize: '13px', color: 'var(--text-muted)' }}>
            Generate cryptographic tokens for Paymenter Server Extension automated provisioning
          </p>
        </div>
        <button onClick={() => setCreateModal(true)} className="btn btn-primary">
          <Plus size={16} /> Generate New Key
        </button>
      </div>

      {/* Guide Callout Card */}
      <div className="card" style={{
        background: 'rgba(59, 130, 246, 0.06)',
        border: '1px solid rgba(59, 130, 246, 0.25)',
        marginBottom: '20px'
      }}>
        <div style={{ display: 'flex', gap: '14px', alignItems: 'flex-start' }}>
          <div style={{ color: 'var(--text-main)', marginTop: '2px' }}>
            <Info size={20} />
          </div>
          <div style={{ fontSize: '13px', lineHeight: 1.6, color: 'var(--text-muted)' }}>
            <strong style={{ color: 'var(--text-main)' }}>How Paymenter Connects:</strong>
            <p style={{ marginTop: '4px' }}>
              Install the <code>DatabaseManager</code> extension in Paymenter at <code>app/Extensions/Servers/DatabaseManager/</code>.
              Configure the <strong>Panel URL</strong> with your panel's address (e.g. <code>http://your-vps-ip:8000</code>) and paste one of the generated <strong>API Keys</strong> below.
            </p>
          </div>
        </div>
      </div>

      {/* Keys Table */}
      <div className="card">
        {loading ? (
          <div style={{ padding: '30px', textAlign: 'center', color: 'var(--text-muted)' }}>Loading API keys...</div>
        ) : keys.length === 0 ? (
          <div style={{ padding: '40px', textAlign: 'center', color: 'var(--text-muted)' }}>
            No API keys configured yet.
          </div>
        ) : (
          <div className="table-responsive">
            <table className="table">
              <thead>
                <tr>
                  <th>Key Description</th>
                  <th>Prefix Token</th>
                  <th>Permissions</th>
                  <th>Last Used</th>
                  <th>Created Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {keys.map((k) => (
                  <tr key={k.id}>
                    <td>
                      <div style={{ fontWeight: 600 }}>{k.name}</div>
                    </td>
                    <td>
                      <code style={{ background: 'var(--bg-input)', padding: '3px 6px', borderRadius: '4px', color: 'var(--text-main)' }}>
                        {k.key_prefix}...
                      </code>
                    </td>
                    <td>
                      <span className="badge badge-neutral">Full Access (*)</span>
                    </td>
                    <td style={{ fontSize: '12px', color: 'var(--text-muted)' }}>
                      {k.last_used_at ? new Date(k.last_used_at).toLocaleDateString('id-ID', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'Never used'}
                    </td>
                    <td style={{ fontSize: '12px', color: 'var(--text-muted)' }}>
                      {new Date(k.created_at).toLocaleDateString('id-ID', { month: 'short', day: 'numeric', year: 'numeric' })}
                    </td>
                    <td>
                      <span className={`badge ${k.is_active ? 'badge-success' : 'badge-danger'}`}>
                        {k.is_active ? 'Enabled' : 'Disabled'}
                      </span>
                    </td>
                    <td>
                      <div style={{ display: 'flex', gap: '6px' }}>
                        <button
                          onClick={() => handleToggle(k.id)}
                          className="btn btn-secondary btn-sm"
                          title={k.is_active ? 'Disable Key' : 'Enable Key'}
                        >
                          <Power size={13} color={k.is_active ? '#f59e0b' : '#10b981'} />
                        </button>
                        <button
                          onClick={() => handleDelete(k.id, k.name)}
                          className="btn btn-danger btn-sm"
                          title="Revoke & Delete Key"
                        >
                          <Trash2 size={13} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Generate Key Modal */}
      {createModal && (
        <div className="modal-overlay" onClick={() => setCreateModal(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h3 className="modal-title">Generate Paymenter API Key</h3>
              <button onClick={() => setCreateModal(false)} style={{ color: 'var(--text-muted)' }}>
                <X size={18} />
              </button>
            </div>

            <form onSubmit={handleCreate}>
              <div className="form-group">
                <label className="form-label">Key Friendly Name</label>
                <input
                  type="text"
                  className="form-control"
                  placeholder="e.g. Paymenter Main Billing VPS"
                  value={keyName}
                  onChange={(e) => setKeyName(e.target.value)}
                  required
                />
              </div>

              <div className="modal-footer">
                <button type="button" className="btn btn-secondary" onClick={() => setCreateModal(false)}>
                  Cancel
                </button>
                <button type="submit" className="btn btn-primary">
                  Generate Key
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* One-Time Token Reveal Modal */}
      {createdToken && (
        <div className="modal-overlay">
          <div className="modal-content">
            <div className="modal-header">
              <h3 className="modal-title">API Key Generated</h3>
            </div>

            <p style={{ fontSize: '13px', color: 'var(--text-muted)', marginBottom: '14px', lineHeight: 1.5 }}>
              Please copy this secret key now. For security purposes, this plaintext token will <strong>never be shown again</strong>.
            </p>

            <div className="mono-box" style={{ padding: '12px', marginBottom: '16px' }}>
              <span style={{ wordBreak: 'break-all', fontWeight: 600 }}>{createdToken}</span>
              <button onClick={() => copyToClipboard(createdToken)} style={{ color: 'var(--text-main)' }}>
                <Copy size={16} />
              </button>
            </div>

            <div className="modal-footer">
              <button
                type="button"
                className="btn btn-primary"
                onClick={() => {
                  copyToClipboard(createdToken);
                  setCreatedToken(null);
                }}
              >
                Copy & Finish
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default AdminApiKeys;
