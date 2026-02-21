import React, { useEffect, useState } from 'react'
import { Mail, Phone, Globe, MapPin } from 'lucide-react'

export default function ContactProfile({ conversationId, metadata }) {
  const [contact, setContact] = useState(null)

  useEffect(() => {
    let mounted = true

    if (metadata) {
      setContact({
        name: metadata.contact_name,
        avatar: metadata.contact_avatar,
        channel: metadata.channel,
        email: metadata.contact_email,
        phone: metadata.contact_phone,
        location: metadata.contact_metadata?.raw_profile?.location || metadata.contact_metadata?.raw_profile?.city || 'Location Unknown'
      })
      return
    }

    if (!conversationId) return
    setContact(null)
    fetch(`/api/conversations/${conversationId}/messages`).then(r => r.json()).then((resp) => {
      if (!mounted) return
      const conv = resp.conversation || null
      if (conv) {
        setContact({
          name: conv.contact_name,
          avatar: conv.contact_avatar,
          channel: conv.channel,
          email: conv.contact_email,
          phone: conv.contact_phone,
          location: conv.contact_metadata?.raw_profile?.location || conv.contact_metadata?.raw_profile?.city || 'Location Unknown'
        })
      }
    }).catch(() => setContact(null))
    return () => (mounted = false)
  }, [conversationId, metadata])

  if (!conversationId) return (
    <div className="p-6 text-center">
      <div className="w-16 h-16 rounded-2xl bg-slate-900 border border-white/5 flex items-center justify-center mx-auto mb-4">
        <Globe size={24} className="text-slate-600" />
      </div>
      <p className="text-sm text-slate-500 font-medium tracking-tight">Select a conversation to view profile</p>
    </div>
  )

  return (
    <div className="p-6 border-b border-white/5 bg-gradient-to-b from-white/[0.02] to-transparent">
      <div className="flex flex-col items-center text-center">
        <div className="relative mb-4 group">
          <div className="absolute -inset-1 bg-gradient-to-tr from-emerald-500 to-cyan-500 rounded-full blur opacity-25 group-hover:opacity-40 transition-opacity"></div>
          <img
            src={contact?.avatar || `https://ui-avatars.com/api/?name=${contact?.name || 'U'}&background=0D161E&color=fff`}
            className="w-20 h-20 rounded-full border-2 border-slate-900 relative z-10"
            alt="Profile"
          />
          <div className="absolute bottom-0 right-0 w-5 h-5 bg-emerald-500 border-4 border-slate-900 rounded-full z-20"></div>
        </div>

        <h2 className="text-lg font-bold text-white mb-1">{contact?.name || 'Loading...'}</h2>
        <span className="px-2.5 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-[10px] font-bold text-emerald-400 uppercase tracking-wider mb-6">
          Premium Client
        </span>

        <div className="w-full space-y-3">
          <div className="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/5 hover:bg-white/[0.08] transition-colors group">
            <Mail size={16} className="text-slate-500 group-hover:text-emerald-400 transition-colors" />
            <span className="text-xs text-slate-300 truncate font-medium">{contact?.email || 'N/A'}</span>
          </div>
          {contact?.phone && (
            <div className="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/5 hover:bg-white/[0.08] transition-colors group">
              <Phone size={16} className="text-slate-500 group-hover:text-emerald-400 transition-colors" />
              <span className="text-xs text-slate-300 truncate font-medium">{contact.phone}</span>
            </div>
          )}
          <div className="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/5 hover:bg-white/[0.08] transition-colors group">
            <MapPin size={16} className="text-slate-500 group-hover:text-emerald-400 transition-colors" />
            <span className="text-xs text-slate-300 font-medium">{contact?.location || 'N/A'}</span>
          </div>
        </div>
      </div>
    </div>
  )
}
