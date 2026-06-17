<style>
.aprendiz-form-shell .tab-button {
    padding-bottom: 0.5rem;
    font-weight: 500;
    transition: all 0.2s ease;
    border-bottom: 2px solid transparent;
}
.aprendiz-form-shell .tab-button.active {
    border-bottom-color: #cacaca;
    color: #1f2937;
}
.aprendiz-form-shell .tab-button.inactive {
    color: #abb6ca;
}
.aprendiz-form-shell .tab-button.inactive:hover {
    color: #374151;
}
.aprendiz-form-shell .form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}
@media (max-width: 640px) {
    .aprendiz-form-shell .form-grid {
        grid-template-columns: 1fr;
    }
}
.aprendiz-form-shell .form-field {
    margin-bottom: 0.5rem;
}
.aprendiz-form-shell .form-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 500;
    color: #abb6ca;
    margin-bottom: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.aprendiz-form-shell .form-input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.875rem;
    transition: all 0.2s;
    background: white;
}
.aprendiz-form-shell .form-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}
.aprendiz-form-shell .form-field.md\:col-span-2 {
    grid-column: span 2 / span 2;
}
@media (max-width: 640px) {
    .aprendiz-form-shell .form-field.md\:col-span-2 {
        grid-column: span 1 / span 1;
    }
}
</style>
