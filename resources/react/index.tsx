import React from 'react'
import { createRoot } from 'react-dom/client'
import App from './app'
import '../css/style.css'

const div = document.createElement('div')
document.body.appendChild(div)
createRoot(div).render(<App />)
