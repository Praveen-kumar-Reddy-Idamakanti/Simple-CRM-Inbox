import React, { useState } from 'react'
import Sidebar from './components/layout/Sidebar'
import ChatPanel from './components/layout/ChatPanel'
import InsightPanel from './components/layout/InsightPanel'
import './index.css'

export default function App() {
  const [selectedConversation, setSelectedConversation] = useState(null)
  const [currentMetadata, setCurrentMetadata] = useState(null)

  return (
    <div className="flex bg-[#020609] text-white w-full h-screen overflow-hidden bp986:flex-row flex-col">
      <Sidebar onSelectConversation={(id) => {
        setSelectedConversation(id);
        setCurrentMetadata(null);
      }} selected={selectedConversation} />

      <div className="flex-1 flex flex-col min-w-0 bg-[radial-gradient(circle_at_center,_rgba(16,185,129,0.03)_0%,_transparent_70%)]">
        <ChatPanel
          conversationId={selectedConversation}
          onMetadataLoaded={setCurrentMetadata}
        />
      </div>

      <InsightPanel
        conversationId={selectedConversation}
        metadata={currentMetadata}
        onTagsUpdated={(newTags) => {
          if (currentMetadata) {
            setCurrentMetadata({ ...currentMetadata, tags: newTags })
          }
        }}
      />
    </div>
  )
}
