import React from 'react'
import { Instagram, Facebook, Mail } from 'lucide-react'

export default function ConversationItem({ convo, onSelect, selected }) {
  const getChannelIcon = () => {
    switch (convo.channel) {
      case 'instagram': return <Instagram size={10} className="text-white" />;
      case 'facebook':
      case 'page': return <Facebook size={10} className="text-white" />;
      default: return <Facebook size={10} className="text-white" />;
    }
  }

  const getChannelColor = () => {
    switch (convo.channel) {
      case 'instagram': return 'bg-gradient-to-tr from-purple-500 via-pink-500 to-orange-500';
      case 'facebook':
      case 'page': return 'bg-blue-600';
      default: return 'bg-blue-600';
    }
  }

  return (
    <div
      onClick={onSelect}
      className={`group flex items-center p-3.5 rounded-2xl transition-all duration-300 cursor-pointer mb-1 ${selected
        ? 'bg-emerald-500/10 border border-emerald-500/20 shadow-lg shadow-emerald-500/5'
        : 'bg-transparent border border-transparent hover:bg-white/5 hover:border-white/5'
        }`}
    >
      <div className="relative flex-shrink-0">
        <div className={`absolute -inset-0.5 rounded-full blur-[2px] opacity-0 group-hover:opacity-40 transition-opacity ${selected ? 'opacity-40' : ''} bg-gradient-to-tr from-emerald-500 to-cyan-500`}></div>
        <img
          src={convo.contact_avatar || `https://ui-avatars.com/api/?name=${convo.contact_name || 'U'}&background=132230&color=fff`}
          alt="avatar"
          className="w-11 h-11 rounded-full border-2 border-slate-900 relative z-10 object-cover"
        />
        <span className={`absolute bottom-0 right-0 w-4 h-4 rounded-full border-2 border-slate-900 flex items-center justify-center z-20 ${getChannelColor()}`}>
          {getChannelIcon()}
        </span>
      </div>

      <div className="ml-3.5 flex-1 min-w-0">
        <div className="flex justify-between items-center mb-0.5">
          <h3 className={`font-bold text-sm truncate transition-colors ${selected ? 'text-emerald-400' : 'text-slate-100 group-hover:text-white'}`}>
            {convo.contact_name || 'Unknown User'}
          </h3>
          <span className="text-[10px] font-medium text-slate-500 tabular-nums">
            {convo.last_message_at ? new Date(convo.last_message_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : ''}
          </span>
        </div>
        <div className="flex items-center gap-2">
          <p className="text-xs text-slate-400 truncate flex-1 leading-snug">
            {convo.last_message_preview || 'No messages yet'}
          </p>
          {convo.unread_count > 0 && (
            <span className="w-2 h-2 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50"></span>
          )}
        </div>
      </div>
    </div>
  )
}
