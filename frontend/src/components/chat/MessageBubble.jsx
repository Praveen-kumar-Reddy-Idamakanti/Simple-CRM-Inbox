import React from 'react'

export default function MessageBubble({ message }) {
  const isAgent = message.sender_type === 'agent'
  const time = message.created_at ? new Date(message.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : ''

  return (
    <div className={`flex w-full ${isAgent ? 'justify-end' : 'justify-start'} mb-4`}>
      <div className={`max-w-[70%] group relative px-5 py-3 rounded-2xl transition-all duration-200 ${isAgent
          ? 'bg-emerald-600 text-white rounded-tr-none shadow-md shadow-emerald-900/10'
          : 'bg-white/5 border border-white/5 text-slate-100 rounded-tl-none hover:bg-white/[0.08]'
        }`}>
        <div className="text-[14px] leading-relaxed break-words">
          {message.formatted_text || message.text}
        </div>
        <div className={`text-[10px] mt-1.5 opacity-0 group-hover:opacity-60 transition-opacity font-medium ${isAgent ? 'text-white' : 'text-slate-400'}`}>
          {time}
        </div>
      </div>
    </div>
  )
}
