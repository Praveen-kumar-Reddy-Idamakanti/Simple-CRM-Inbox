import React from 'react'
import ConversationItem from './ConversationItem'

export default function ConversationList({ conversations = [], onSelect, selected }) {
  return (
    <div className="space-y-2">
      {conversations.length === 0 && <div className="text-sm text-[#8CA3B8]">No conversations</div>}
      {conversations.map((c) => (
        <ConversationItem key={c.id} convo={c} onSelect={() => onSelect(c.id)} selected={selected === c.id} />
      ))}
    </div>
  )
}
