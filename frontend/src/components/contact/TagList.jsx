import React, { useState } from 'react'
import { Tag as TagIcon, X, Plus, Loader2 } from 'lucide-react'
import { addTag, removeTag } from '../../services/api'

export default function TagList({ conversationId, contactSenderId, tags = [], onTagsUpdated }) {
  const [isAdding, setIsAdding] = useState(false)
  const [newTag, setNewTag] = useState('')
  const [busyTag, setBusyTag] = useState(null)

  const handleAddTag = async (e) => {
    e.preventDefault()
    if (!newTag.trim() || !contactSenderId) return

    setBusyTag('adding')
    try {
      const res = await addTag(contactSenderId, newTag.trim())
      if (res.status === 'success' && onTagsUpdated) {
        onTagsUpdated(res.data.tags)
        setNewTag('')
        setIsAdding(false)
      }
    } catch (err) {
      console.error('Failed to add tag', err)
    } finally {
      setBusyTag(null)
    }
  }

  const handleRemoveTag = async (tagName) => {
    if (!contactSenderId) return
    setBusyTag(tagName)
    try {
      const res = await removeTag(contactSenderId, tagName)
      if (res.status === 'success' && onTagsUpdated) {
        onTagsUpdated(res.data.tags)
      }
    } catch (err) {
      console.error('Failed to remove tag', err)
    } finally {
      setBusyTag(null)
    }
  }

  const getTagStyle = (name) => {
    const n = name.toLowerCase()
    if (n.includes('vip')) return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
    if (n.includes('lead')) return 'bg-info/10 text-info border-info/20'
    if (n.includes('priority') || n.includes('urgent')) return 'bg-amber-500/10 text-amber-400 border-amber-500/20'
    return 'bg-slate-500/10 text-slate-400 border-slate-500/20'
  }

  return (
    <div>
      <div className="flex items-center gap-2 mb-3">
        <TagIcon className="text-slate-500" size={14} />
        <h3 className="text-xs font-bold text-slate-500 uppercase tracking-wider">Target Segments</h3>
      </div>

      <div className="flex flex-wrap gap-2">
        {tags.map((tag) => (
          <span
            key={tag}
            className={`flex items-center gap-1.5 px-2.5 py-1 rounded-lg border ${getTagStyle(tag)} text-[11px] font-bold uppercase tracking-tight group transition-all`}
          >
            {tag}
            <button
              onClick={() => handleRemoveTag(tag)}
              disabled={busyTag === tag}
              className="hover:text-white transition-colors p-0.5 rounded-sm hover:bg-white/10"
            >
              {busyTag === tag ? <Loader2 size={10} className="animate-spin" /> : <X size={10} />}
            </button>
          </span>
        ))}

        {isAdding ? (
          <form onSubmit={handleAddTag} className="flex items-center">
            <input
              autoFocus
              value={newTag}
              onChange={(e) => setNewTag(e.target.value)}
              className="bg-slate-900 border border-emerald-500/30 rounded-lg px-2 py-1 text-[11px] text-white focus:outline-none w-20"
              placeholder="Tag name..."
              onBlur={() => !newTag && setIsAdding(false)}
            />
          </form>
        ) : (
          <button
            onClick={() => setIsAdding(true)}
            disabled={!contactSenderId}
            className="flex items-center gap-1 px-2 py-1 rounded-lg border border-white/5 bg-white/5 text-[11px] font-bold text-slate-400 hover:text-white hover:bg-white/10 transition-all disabled:opacity-30"
          >
            <Plus size={10} />
            Add
          </button>
        )}
      </div>
    </div>
  )
}
