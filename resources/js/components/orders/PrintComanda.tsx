import { forwardRef } from 'react';

import { Order, OrderItemOption } from '@/types';

interface DeliveryAddressSnapshot {
    id?: number;
    customer_id?: number;
    label?: string;
    address_line?: string;
    latitude?: number;
    longitude?: number;
    delivery_notes?: string;
    zone?: string;
    is_default?: boolean;
    created_at?: string;
    updated_at?: string;
}

interface PrintComandaProps {
    order: Order & {
        delivery_address?: string | DeliveryAddressSnapshot | null;
    };
}

const getDeliveryAddressText = (address: string | DeliveryAddressSnapshot | null | undefined): string | null => {
    if (!address) return null;
    if (typeof address === 'string') return address;
    // Es un objeto con la estructura de dirección
    const parts: string[] = [];
    if (address.label) parts.push(address.label);
    if (address.address_line) parts.push(address.address_line);
    if (address.delivery_notes) parts.push(`Notas: ${address.delivery_notes}`);
    return parts.length > 0 ? parts.join(' - ') : null;
};

const formatDateTime = (dateString: string): string => {
    return new Date(dateString).toLocaleString('es-GT', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'America/Guatemala',
    });
};

/**
 * Agrupa las opciones por nombre de sección
 */
const groupOptionsBySection = (options: OrderItemOption[]): Record<string, string[]> => {
    const grouped: Record<string, string[]> = {};

    for (const option of options) {
        const sectionName = option.section_name || 'Opciones';
        if (!grouped[sectionName]) {
            grouped[sectionName] = [];
        }
        grouped[sectionName].push(option.name);
    }

    return grouped;
};

export const PrintComanda = forwardRef<HTMLDivElement, PrintComandaProps>(({ order }, ref) => {
    const serviceTypeLabel = order.service_type === 'delivery' ? 'DELIVERY' : 'PARA LLEVAR';

    return (
        <div ref={ref} className="print-comanda hidden print:block">
            <style>
                {`
                    @media print {
                        @page {
                            size: 80mm auto;
                            margin: 0;
                        }

                        body * {
                            visibility: hidden;
                        }

                        .print-comanda,
                        .print-comanda * {
                            visibility: visible !important;
                        }

                        .print-comanda {
                            position: absolute;
                            left: 0;
                            top: 0;
                            width: 80mm;
                            padding: 5mm;
                            font-family: 'Courier New', monospace;
                            font-size: 12px;
                            line-height: 1.4;
                            color: #000;
                            background: #fff;
                        }

                        .comanda-header {
                            text-align: center;
                            border-bottom: 2px dashed #000;
                            padding-bottom: 8px;
                            margin-bottom: 8px;
                        }

                        .comanda-title {
                            font-size: 18px;
                            font-weight: bold;
                            margin: 0;
                        }

                        .comanda-order-number {
                            font-size: 24px;
                            font-weight: bold;
                            margin: 4px 0;
                        }

                        .comanda-service-type {
                            font-size: 16px;
                            font-weight: bold;
                            padding: 4px 8px;
                            border: 2px solid #000;
                            display: inline-block;
                            margin: 4px 0;
                        }

                        .comanda-info {
                            border-bottom: 1px dashed #000;
                            padding-bottom: 8px;
                            margin-bottom: 8px;
                        }

                        .comanda-info-row {
                            display: flex;
                            justify-content: space-between;
                            margin: 2px 0;
                        }

                        .comanda-items {
                            border-bottom: 2px dashed #000;
                            padding-bottom: 8px;
                            margin-bottom: 8px;
                        }

                        .comanda-item {
                            margin-bottom: 12px;
                            padding-bottom: 8px;
                            border-bottom: 1px dotted #ccc;
                        }

                        .comanda-item:last-child {
                            border-bottom: none;
                            margin-bottom: 0;
                        }

                        .comanda-item-header {
                            display: flex;
                            align-items: flex-start;
                            gap: 8px;
                        }

                        .comanda-item-qty {
                            font-size: 20px;
                            font-weight: bold;
                            min-width: 30px;
                            text-align: center;
                            border: 1px solid #000;
                            padding: 2px 4px;
                        }

                        .comanda-item-info {
                            flex: 1;
                        }

                        .comanda-item-category {
                            font-size: 10px;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                            color: #666;
                        }

                        .comanda-item-name {
                            font-size: 14px;
                            font-weight: bold;
                            text-transform: uppercase;
                        }

                        .comanda-item-variant {
                            font-size: 11px;
                            color: #444;
                        }

                        .comanda-item-options {
                            margin-left: 38px;
                            margin-top: 4px;
                        }

                        .comanda-item-prices {
                            margin-left: 38px;
                            margin-top: 6px;
                            padding-top: 4px;
                            border-top: 1px dotted #999;
                            font-size: 11px;
                        }

                        .comanda-price-row {
                            display: flex;
                            justify-content: space-between;
                            margin: 1px 0;
                        }

                        .comanda-price-total {
                            font-weight: bold;
                            border-top: 1px solid #000;
                            padding-top: 2px;
                            margin-top: 2px;
                        }

                        .comanda-item-section {
                            font-size: 11px;
                            margin: 3px 0;
                            line-height: 1.3;
                        }

                        .comanda-item-section-name {
                            font-weight: bold;
                        }

                        .comanda-item-notes {
                            margin-left: 38px;
                            margin-top: 4px;
                            font-size: 12px;
                            font-style: italic;
                            padding: 4px;
                            border: 1px dashed #000;
                            background: #f5f5f5;
                        }

                        .comanda-notes {
                            border: 2px solid #000;
                            padding: 8px;
                            margin-bottom: 8px;
                        }

                        .comanda-notes-title {
                            font-weight: bold;
                            margin-bottom: 4px;
                        }

                        .comanda-delivery {
                            border: 2px solid #000;
                            padding: 8px;
                            margin-bottom: 8px;
                        }

                        .comanda-delivery-title {
                            font-weight: bold;
                            margin-bottom: 4px;
                        }

                        .comanda-footer {
                            text-align: center;
                            font-size: 10px;
                            margin-top: 8px;
                            padding-top: 8px;
                            border-top: 1px dashed #000;
                        }
                    }
                `}
            </style>

            <div className="comanda-header">
                <p className="comanda-title">COMANDA</p>
                <p className="comanda-order-number">#{order.order_number}</p>
                <span className="comanda-service-type">{serviceTypeLabel}</span>
            </div>

            <div className="comanda-info">
                <div className="comanda-info-row">
                    <span>Fecha:</span>
                    <span>{formatDateTime(order.created_at)}</span>
                </div>
                {order.customer && (
                    <>
                        <div className="comanda-info-row">
                            <span>Cliente:</span>
                            <span>{order.customer.full_name}</span>
                        </div>
                        {order.customer.phone && (
                            <div className="comanda-info-row">
                                <span>Tel:</span>
                                <span>{order.customer.phone}</span>
                            </div>
                        )}
                    </>
                )}
            </div>

            <div className="comanda-items">
                {order.items?.map((item) => {
                    const groupedOptions = item.options ? groupOptionsBySection(item.options) : {};
                    const extrasTotal = Number(item.options_price) || 0;
                    const basePrice = (Number(item.unit_price) || 0) - extrasTotal;
                    const totalPrice = Number(item.total_price) || 0;

                    return (
                        <div key={item.id} className="comanda-item">
                            <div className="comanda-item-header">
                                <span className="comanda-item-qty">{item.quantity}</span>
                                <div className="comanda-item-info">
                                    {item.category && (
                                        <div className="comanda-item-category">{item.category}</div>
                                    )}
                                    <div className="comanda-item-name">{item.name}</div>
                                    {item.variant && (
                                        <div className="comanda-item-variant">{item.variant}</div>
                                    )}
                                </div>
                            </div>
                            {Object.keys(groupedOptions).length > 0 && (
                                <div className="comanda-item-options">
                                    {Object.entries(groupedOptions).map(([sectionName, optionNames]) => (
                                        <div key={sectionName} className="comanda-item-section">
                                            <span className="comanda-item-section-name">{sectionName}:</span>{' '}
                                            {optionNames.join(', ')}
                                        </div>
                                    ))}
                                </div>
                            )}
                            {item.notes && (
                                <div className="comanda-item-notes">
                                    NOTA: {item.notes}
                                </div>
                            )}
                            <div className="comanda-item-prices">
                                <div className="comanda-price-row">
                                    <span>Base:</span>
                                    <span>Q{(basePrice > 0 ? basePrice : Number(item.unit_price) || 0).toFixed(2)}</span>
                                </div>
                                {extrasTotal > 0 && (
                                    <div className="comanda-price-row">
                                        <span>Extras:</span>
                                        <span>Q{extrasTotal.toFixed(2)}</span>
                                    </div>
                                )}
                                <div className="comanda-price-row comanda-price-total">
                                    <span>TOTAL:</span>
                                    <span>Q{totalPrice.toFixed(2)}</span>
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>

            {order.notes && (
                <div className="comanda-notes">
                    <div className="comanda-notes-title">NOTAS DEL PEDIDO:</div>
                    <div>{order.notes}</div>
                </div>
            )}

            {order.service_type === 'delivery' && order.delivery_address && (
                <div className="comanda-delivery">
                    <div className="comanda-delivery-title">DIRECCION DE ENTREGA:</div>
                    <div>{getDeliveryAddressText(order.delivery_address)}</div>
                </div>
            )}

            <div className="comanda-footer">
                <p>Impreso: {formatDateTime(new Date().toISOString())}</p>
            </div>
        </div>
    );
});

PrintComanda.displayName = 'PrintComanda';

export default PrintComanda;
