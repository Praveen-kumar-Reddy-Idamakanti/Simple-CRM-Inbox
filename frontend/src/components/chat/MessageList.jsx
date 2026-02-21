import React, { useEffect, useRef } from 'react'
import MessageBubble from './MessageBubble'
import { MessageSquare, Loader2 } from 'lucide-react'

export default function MessageList({ messages = [], loading, conversationId }) {
  const scrollRef = useRef(null)

  useEffect(() => {
    if (scrollRef.current) {
      scrollRef.current.scrollTop = scrollRef.current.scrollHeight
    }
  }, [messages])

  if (!conversationId) {
    return (
      <div className="flex-1 flex flex-col items-center justify-center p-6 text-center opacity-40">
        <div className="w-16 h-16 rounded-3xl bg-slate-900 border border-white/5 flex items-center justify-center mb-4">
          <MessageSquare size={32} className="text-slate-600" />
        </div>
        <h3 className="text-lg font-bold text-white mb-2">Your AI CRM Workspace</h3>
        <p className="max-w-[240px] text-sm text-slate-400">Select a conversation from the sidebar to start intelligent engagement.</p>
      </div>
    )
  }

  if (loading && messages.length === 0) {
    return (
      <div className="flex-1 flex items-center justify-center">
        <Loader2 className="animate-spin text-emerald-500" size={32} />
      </div>
    )
  }

  return (
    <div ref={scrollRef} className="flex-1 overflow-y-auto p-8 space-y-2 custom-scrollbar">
      {messages.length === 0 && !loading && (
        <div className="py-12 text-center text-slate-500 text-sm">
          No messages yet. Send a greeting to start!
        </div>
      )}
      {messages.map((m) => (
        <MessageBubble key={m.id || m.created_at} message={m} />
      ))}
    </div>
  )
}
