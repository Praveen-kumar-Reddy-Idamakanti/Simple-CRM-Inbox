import React, { useEffect, useState } from 'react'
import { fetchConversations } from '../../services/api'
import ConversationList from '../conversation/ConversationList'
import { Search, Sparkles } from 'lucide-react'
import logo from '../../assets/image.png'

export default function Sidebar({ onSelectConversation, selected }) {
  const [conversations, setConversations] = useState([])

  const [searchTerm, setSearchTerm] = useState('')

  useEffect(() => {
    let mounted = true
    const fetch = () => {
      fetchConversations(searchTerm ? `search=${searchTerm}` : '').then((res) => {
        if (!mounted) return
        setConversations(res.data || [])
      }).catch(() => setConversations([]))
    }

    const delayDebounceFn = setTimeout(() => {
      fetch()
    }, searchTerm ? 300 : 0)

    const interval = setInterval(fetch, 5000)

    return () => {
      mounted = false
      clearTimeout(delayDebounceFn)
      clearInterval(interval)
    }
  }, [searchTerm])

  return (
    <aside className="w-full bp986:w-[320px] bp986:flex-shrink-0 glass-panel h-full flex flex-col">
      <div className="p-6">
        <div className="flex bg-blue-300 items-center justify-center p-3 rounded-xl mb-8 overflow-hidden h-16 shadow-lg shadow-black/20">
          <img src={logo} alt="Logo" className="h-40 w-auto object-cover " />
        </div>

        <div className="relative group">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-emerald-400 transition-colors" size={18} />
          <input
            placeholder="Search conversations..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full bg-slate-900/50 border border-white/5 rounded-xl pl-10 pr-4 py-2.5 text-sm text-white placeholder:text-slate-500 focus:outline-none focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/20 transition-all"
          />
        </div>
      </div>

      <div className="flex-1 overflow-y-auto px-3 pb-4">
        <div className="px-3 mb-2">
          <h2 className="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Recent Inboxes</h2>
        </div>
        <ConversationList conversations={conversations} onSelect={onSelectConversation} selected={selected} />
      </div>
    </aside>
  )
}
