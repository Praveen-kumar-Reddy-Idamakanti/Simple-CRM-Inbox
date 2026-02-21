import React from 'react'
import ContactProfile from '../contact/ContactProfile'
import TagList from '../contact/TagList'
import { Zap, BrainCircuit, History } from 'lucide-react'

export default function InsightPanel({ conversationId, metadata, onTagsUpdated }) {
  return (
    <aside className="hidden bp986:flex flex-col w-[360px] glass-panel h-full border-l border-white/5 overflow-hidden">
      <div className="flex-1 overflow-y-auto custom-scrollbar">
        <ContactProfile conversationId={conversationId} metadata={metadata} />

        <div className="px-6 py-4">
          <TagList
            conversationId={conversationId}
            contactSenderId={metadata?.contact_sender_id}
            tags={metadata?.tags || []}
            onTagsUpdated={onTagsUpdated}
          />
        </div>

        {/* AI Insight Section */}
        <div className="mx-6 my-4 p-5 rounded-2xl bg-transparent border border-white/10 shadow-sm relative overflow-hidden group">
          <div className="absolute inset-0 bg-white/[0.02] opacity-0 group-hover:opacity-100 transition-opacity"></div>
          <div className="flex items-center gap-2 mb-3 relative z-10">
            <BrainCircuit className="text-emerald-400" size={18} />
            <h3 className="text-sm font-bold text-white uppercase tracking-wider">AI Summary</h3>
          </div>
          <p className="text-sm text-slate-300 leading-relaxed italic">
            "The customer is inquiring about the premium subscription options. They seem particularly interested in the automation features for email sequences."
          </p>
          <div className="mt-4 flex items-center gap-4 border-t border-white/5 pt-4 relative z-10">
            <div className="flex flex-col">
              <span className="text-[10px] text-slate-500 uppercase font-bold">Sentiment</span>
              <span className="text-xs text-emerald-400 font-semibold">Positive</span>
            </div>
            <div className="flex flex-col">
              <span className="text-[10px] text-slate-500 uppercase font-bold">Intent</span>
              <span className="text-xs text-info font-semibold">Inquiry</span>
            </div>
          </div>
        </div>

        {/* Activity Section */}
        <div className="px-6 py-4">
          <div className="flex items-center gap-2 mb-4">
            <History className="text-slate-400" size={18} />
            <h3 className="text-sm font-bold text-white uppercase tracking-wider">Recent Activity</h3>
          </div>
          <div className="space-y-4">
            {[1, 2].map((i) => (
              <div key={i} className="flex gap-3 relative before:absolute before:left-2 before:top-6 before:bottom-[-20px] before:w-[1px] before:bg-white/5 last:before:hidden">
                <div className="w-4 h-4 rounded-full bg-slate-800 border border-white/10 mt-1 z-10"></div>
                <div>
                  <p className="text-xs text-slate-200">Replied to welcome email</p>
                  <p className="text-[10px] text-slate-500 mt-0.5">2 days ago</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="p-6 border-t border-white/5">
        <button className="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold transition-all flex items-center justify-center gap-2 shadow-lg shadow-emerald-900/20">
          <Zap size={16} fill="currentColor" />
          Take Action
        </button>
      </div>
    </aside>
  )
}
