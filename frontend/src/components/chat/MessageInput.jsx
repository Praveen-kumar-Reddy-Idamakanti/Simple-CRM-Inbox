import React, { useState, useEffect } from 'react'
import { sendReply, getAiSuggestion } from '../../services/api'
import { Send, Paperclip, Smile, Image as ImageIcon, Sparkles, X, Loader2 } from 'lucide-react'

export default function MessageInput({ conversationId, onMessageSent }) {
  const [text, setText] = useState('')
  const [sending, setSending] = useState(false)
  const [suggestion, setSuggestion] = useState(null)
  const [loadingSuggestion, setLoadingSuggestion] = useState(false)

  useEffect(() => {
    if (conversationId) {
      handleGetSuggestion()
    } else {
      setSuggestion(null)
    }
  }, [conversationId])

  const handleGetSuggestion = async () => {
    if (!conversationId) return
    setLoadingSuggestion(true)
    try {
      const res = await getAiSuggestion(conversationId)
      if (res.status === 'success') {
        setSuggestion(res.suggestion)
      }
    } catch (e) {
      console.error('Failed to fetch AI suggestion', e)
    } finally {
      setLoadingSuggestion(false)
    }
  }

  const useSuggestion = () => {
    setText(suggestion)
    setSuggestion(null)
  }

  const handleSend = async () => {
    if (!conversationId || !text.trim()) return
    const messageText = text.trim()
    setSending(true)
    try {
      const res = await sendReply(conversationId, messageText)
      if (res.status === 'success' && onMessageSent) {
        onMessageSent({
          id: res.data.message_id || Math.random(),
          text: messageText,
          sender_type: 'agent',
          created_at: res.data.created_at || new Date().toISOString()
        })
      }
      setText('')
    } catch (e) {
      console.error('Failed to send reply', e)
    } finally {
      setSending(false)
    }
  }

  const handleKeyDown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault()
      handleSend()
    }
  }

  return (
    <div className="relative group/input">
      {/* AI Suggestion Bubble */}
      {(suggestion || loadingSuggestion) && (
        <div className="absolute -top-12 left-0 right-0 animate-in fade-in slide-in-from-bottom-2 duration-300">
          <div className="mx-auto max-w-max flex items-center gap-3 px-4 py-2 rounded-2xl bg-slate-900/90 border border-emerald-500/30 backdrop-blur-xl shadow-2xl shadow-emerald-500/10">
            <div className="flex items-center gap-2">
              <Sparkles size={14} className="text-emerald-400" />
              <span className="text-[11px] font-bold text-emerald-400/80 uppercase tracking-wider">AI suggested Sentence from previous chat</span>
            </div>
            <div className="h-3 w-[1px] bg-white/10 mx-1" />

            {loadingSuggestion ? (
              <Loader2 size={12} className="animate-spin text-slate-400" />
            ) : (
              <>
                <p className="text-xs text-slate-200 line-clamp-1 max-w-[200px] italic">
                  "{suggestion}"
                </p>
                <button
                  onClick={useSuggestion}
                  className="px-2.5 py-1 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/40 text-emerald-400 text-[10px] font-bold transition-all border border-emerald-500/20 hover:scale-105"
                >
                  Use Reply
                </button>
                <button
                  onClick={() => setSuggestion(null)}
                  className="p-1 rounded-full hover:bg-white/5 text-slate-500 hover:text-white transition-colors"
                >
                  <X size={12} />
                </button>
              </>
            )}
          </div>
        </div>
      )}

      <div className="relative glass-card rounded-2xl p-2 flex flex-col gap-2 group-focus-within:border-emerald-500/30 group-focus-within:bg-white/[0.08] transition-all duration-300">
        <textarea
          value={text}
          onChange={(e) => setText(e.target.value)}
          onKeyDown={handleKeyDown}
          placeholder="Type your message here..."
          className="w-full bg-transparent border-none focus:ring-0 text-sm text-slate-100 placeholder:text-slate-500 resize-none px-4 pt-3 min-h-[48px] custom-scrollbar"
          rows={1}
        />

        <div className="flex items-center justify-between px-2 pb-1">
          <div className="flex items-center gap-1">
            <button className="p-2 rounded-lg text-slate-500 hover:text-white hover:bg-white/5 transition-all">
              <Paperclip size={18} />
            </button>
            <button className="p-2 rounded-lg text-slate-500 hover:text-white hover:bg-white/5 transition-all">
              <ImageIcon size={18} />
            </button>
            <button className="p-2 rounded-lg text-slate-500 hover:text-white hover:bg-white/5 transition-all">
              <Smile size={18} />
            </button>
          </div>

          <button
            onClick={handleSend}
            disabled={sending || !conversationId || !text.trim()}
            className="flex items-center gap-2 px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-bold transition-all shadow-lg shadow-emerald-900/20 active:scale-95"
          >
            {sending ? 'Sending...' : 'Send Message'}
            {!sending && <Send size={14} />}
          </button>
        </div>
      </div>
    </div>
  )
}
