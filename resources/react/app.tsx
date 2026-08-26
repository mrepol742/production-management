import React, { Suspense } from 'react'
import { BrowserRouter as Router, Route, Routes } from 'react-router-dom'
import Home from './views/home'

const App: React.FC = () => {
    return (
        <Router>
            <Suspense
                fallback={
                    <div className="flex justify-content-center align-items-center">
                        <div className="spinner" color="primary" role="status">
                            <span className="hidden">Loading...</span>
                        </div>
                    </div>
                }
            >
                <Routes>
                    <Route path="*" element={<Home />} />
                </Routes>
            </Suspense>
        </Router>
    )
}
export default App
