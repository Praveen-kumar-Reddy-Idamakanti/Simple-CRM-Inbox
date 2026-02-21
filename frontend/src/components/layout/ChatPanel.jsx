import React, { useEffect, useState } from 'react'
import MessageList from '../chat/MessageList'
import MessageInput from '../chat/MessageInput'
import { Bot, User, Phone, MoreVertical, Loader2 } from 'lucide-react'
import { fetchMessages } from '../../services/api'

export default function ChatPanel({ conversationId, onMetadataLoaded }) {
  const [messages, setMessages] = useState([])
  const [metadata, setMetadata] = useState(null)
  const [loading, setLoading] = useState(false)
  const [isTyping, setIsTyping] = useState(false)

  useEffect(() => {
    let mounted = true
    if (!conversationId) {
      setMessages([])
      setMetadata(null)
      return
    }

    const fetch = () => {
      fetchMessages(conversationId)
        .then((res) => {
          if (!mounted) return
          setMessages((res.data || []).reverse())
          setMetadata(res.conversation || null)
          if (onMetadataLoaded && res.conversation) {
            onMetadataLoaded(res.conversation)
          }
        })
        .catch(() => {
          setMessages([])
          setMetadata(null)
        })
        .finally(() => {
          if (mounted) setLoading(false)
        })
    }

    setLoading(true)
    fetch()

    // Poll for new messages every 5 seconds
    const interval = setInterval(fetch, 5000)

    return () => {
      mounted = false
      clearInterval(interval)
    }
  }, [conversationId, onMetadataLoaded])

  const handleMessageSent = (newMsg) => {
    setMessages((prev) => [...prev, newMsg])

    // If agent sent a message, show typing indicator for a bit (simulating contact's reaction)
    if (newMsg.sender_type === 'agent') {
      setIsTyping(true)
      setTimeout(() => setIsTyping(false), 2000)
    }
  }

  return (
    <main className="flex-1 flex flex-col bg-transparent min-w-0 relative h-full">
      {/* Chat Header */}
      <div className="h-20 flex items-center justify-between px-8 border-b border-white/5 backdrop-blur-md sticky top-0 z-10">
        <div className="flex items-center gap-4">
          <div className="w-10 h-10 rounded-full bg-slate-800 border border-white/10 flex items-center justify-center overflow-hidden">
            {metadata?.contact_avatar ? (
              <img src={metadata.contact_avatar} alt="Avatar" className="w-full h-full object-cover" />
            ) : (
              <User size={20} className="text-emerald-400" />
            )}
          </div>
          <div>
            <h2 className="text-base font-semibold text-white">
              {metadata ? metadata.contact_name : (conversationId ? 'Loading...' : 'Select a conversation')}
            </h2>
            <div className="flex items-center gap-2">
              <span className={`w-2 h-2 rounded-full ${conversationId ? 'bg-emerald-500 animate-pulse' : 'bg-slate-600'}`}></span>
              <span className="text-xs text-slate-400 font-medium">
                {isTyping ? 'Typing...' : (metadata?.channel ? metadata.channel.toUpperCase() : (conversationId ? 'Active now' : 'Offline'))}
              </span>
            </div>
          </div>
        </div>

        <div className="flex items-center gap-3">
          {loading && <Loader2 size={18} className="text-slate-500 animate-spin mr-2" />}
          <button className="p-2.5 rounded-xl bg-white/5 border border-white/10 text-slate-400 hover:text-white hover:bg-white/10 transition-all">
            <Phone size={18} />
          </button>
          <button className="p-2.5 rounded-xl bg-white/5 border border-white/10 text-slate-400 hover:text-white hover:bg-white/10 transition-all">
            <MoreVertical size={18} />
          </button>
        </div>
      </div>

      {/* Messages Area */}
      <div className="flex-1 overflow-hidden flex flex-col relative">
        <MessageList messages={messages} loading={loading} conversationId={conversationId} />

        {/* Typing Indicator */}
        {isTyping && (
          <div className="absolute bottom-4 left-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
            <div className="flex gap-1.5 p-3 rounded-2xl bg-white/5 border border-white/5">
              <div className="w-1.5 h-1.5 rounded-full bg-slate-500 animate-bounce [animation-delay:-0.3s]"></div>
              <div className="w-1.5 h-1.5 rounded-full bg-slate-500 animate-bounce [animation-delay:-0.15s]"></div>
              <div className="w-1.5 h-1.5 rounded-full bg-slate-500 animate-bounce"></div>
            </div>
          </div>
        )}
      </div>

      {/* Input Area */}
      <div className="px-8 pb-8">
        <MessageInput conversationId={conversationId} onMessageSent={handleMessageSent} />
      </div>
    </main>
  )
}
