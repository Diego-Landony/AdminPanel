import React from 'react'
import { 
    SidebarGroup, 
    SidebarGroupLabel, 
    SidebarMenu, 
    SidebarMenuButton, 
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem
} from '@/components/ui/sidebar';
import { useSidebar } from '@/components/ui/sidebar';
import { SidebarMenuAction } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
} from '@/components/ui/dropdown-menu';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Menú</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => {
                    if (item.items && item.items.length > 0) {
                        return <GroupItem key={item.title} item={item} />
                    }

                    // Item normal sin subitems
                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={item.href ? usePage().url.startsWith(item.href) : false}
                                tooltip={{ children: item.title }}
                            >
                                <Link href={item.href || '#'} prefetch>
                                    {item.icon && (
                                        // render top-level icon
                                        <item.icon className="mr-2 h-4 w-4" />
                                    )}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    )
                })}
            </SidebarMenu>
        </SidebarGroup>
    )
}

function GroupItem({ item }: { item: NavItem }) {
    const page = usePage()
    const { state } = useSidebar()

    const isSubItemActive = item.items?.some(
        (subItem) => subItem.href && page.url.startsWith(subItem.href)
    )

    const [open, setOpen] = React.useState<boolean>(() => !!isSubItemActive)

    React.useEffect(() => {
        if (isSubItemActive) setOpen(true)
    }, [isSubItemActive])

    const firstHref = item.items?.[0]?.href

    return (
        <Collapsible key={item.title} open={open} onOpenChange={setOpen} className="group/collapsible">
            <SidebarMenuItem>
                {state === 'collapsed' ? (
                    // When sidebar is collapsed, show a click-triggered dropdown that
                    // only contains the children. The button itself does not navigate.
                    <>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <SidebarMenuButton asChild isActive={!!isSubItemActive}>
                                    <div className="flex items-center">
                                        {item.icon && <item.icon className="mr-2 h-4 w-4" />}
                                        <span>{item.title}</span>
                                    </div>
                                </SidebarMenuButton>
                            </DropdownMenuTrigger>

                            <DropdownMenuContent sideOffset={8} className="w-48">
                                {item.items?.map((subItem) => (
                                    <DropdownMenuItem key={subItem.title} asChild>
                                        <Link href={subItem.href || '#'} prefetch className="block w-full">
                                            {subItem.title}
                                        </Link>
                                    </DropdownMenuItem>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </>
                    ) : (
                    // Expanded sidebar: clicking the title toggles the group's open state
                    <>
                        <SidebarMenuButton isActive={!!isSubItemActive} onClick={() => setOpen((v) => !v)}>
                            {item.icon && <item.icon className="mr-2 h-4 w-4" />}
                            <span>{item.title}</span>
                        </SidebarMenuButton>

                        <SidebarMenuAction aria-expanded={open} onClick={() => setOpen((v) => !v)}>
                            <ChevronRight className={`transition-transform duration-200 ${open ? 'rotate-90' : ''}`} />
                        </SidebarMenuAction>

                        <CollapsibleContent>
                            <SidebarMenuSub>
                                {item.items?.map((subItem) => (
                                    <SidebarMenuSubItem key={subItem.title}>
                                        <SidebarMenuSubButton asChild isActive={subItem.href ? page.url.startsWith(subItem.href) : false}>
                                            <Link href={subItem.href || '#'} prefetch>
                                                <span>{subItem.title}</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                ))}
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </>
                )}
            </SidebarMenuItem>
        </Collapsible>
    )
}
